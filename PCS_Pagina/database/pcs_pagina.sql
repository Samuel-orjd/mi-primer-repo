-- =====================================================================
--  Base de datos: pcs_pagina  (tienda web de PC Gamer)
--  Modelo según la sección 5 del documento "Análisis del proyecto".
--  Compatible con MariaDB / MySQL de XAMPP.
--
--  CÓMO IMPORTARLA:
--    1. Inicia Apache y MySQL en el panel de XAMPP.
--    2. Abre http://localhost/phpmyadmin
--    3. Pestaña "Importar" > elige este archivo > botón "Importar".
--
--  Se puede importar varias veces sin errores (no duplica datos).
--  Para empezar de cero, ejecuta antes:  DROP DATABASE pcs_pagina;
--
--  Usuarios de prueba: la contraseña de los 3 es  Pcs2026*
--  (datos de ejemplo solo para desarrollo local).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS pcs_pagina
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pcs_pagina;

-- ---------------------------------------------------------------------
-- 1. usuarios (clientes y administradores)  <-- tabla del CRUD
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario      INT NOT NULL AUTO_INCREMENT,
  nombre          VARCHAR(80)  NOT NULL,
  apellido        VARCHAR(80)  NOT NULL,
  correo          VARCHAR(120) NOT NULL,
  contrasena_hash VARCHAR(255) NOT NULL,
  telefono        VARCHAR(20)  DEFAULT NULL,
  rol             VARCHAR(20)  NOT NULL DEFAULT 'cliente',
  fecha_registro  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY uq_usuarios_correo (correo),
  CONSTRAINT chk_usuarios_rol CHECK (rol IN ('cliente', 'administrador'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. direcciones
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS direcciones (
  id_direccion  INT NOT NULL AUTO_INCREMENT,
  id_usuario    INT NOT NULL,
  direccion     VARCHAR(150) NOT NULL,
  ciudad        VARCHAR(80)  NOT NULL,
  region        VARCHAR(80)  NOT NULL,
  codigo_postal VARCHAR(10)  DEFAULT NULL,
  es_principal  BOOLEAN      NOT NULL DEFAULT 0,
  PRIMARY KEY (id_direccion),
  CONSTRAINT fk_direcciones_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. categorias (gamas: entrada, media, alta, streaming)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
  id_categoria INT NOT NULL AUTO_INCREMENT,
  nombre       VARCHAR(60)  NOT NULL,
  descripcion  VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id_categoria),
  UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. productos (PCs gamer)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS productos (
  id_producto     INT NOT NULL AUTO_INCREMENT,
  id_categoria    INT NOT NULL,
  nombre          VARCHAR(120)  NOT NULL,
  marca           VARCHAR(60)   DEFAULT NULL,
  descripcion     TEXT          DEFAULT NULL,
  precio          DECIMAL(12,2) NOT NULL,
  stock           INT           NOT NULL DEFAULT 0,
  procesador      VARCHAR(80)   DEFAULT NULL,
  tarjeta_grafica VARCHAR(80)   DEFAULT NULL,
  memoria_ram_gb  SMALLINT      DEFAULT NULL,
  almacenamiento  VARCHAR(80)   DEFAULT NULL,
  placa_madre     VARCHAR(80)   DEFAULT NULL,
  fuente_poder    VARCHAR(60)   DEFAULT NULL,
  gabinete        VARCHAR(80)   DEFAULT NULL,
  garantia_meses  SMALLINT      DEFAULT NULL,
  destacado       BOOLEAN       NOT NULL DEFAULT 0,
  activo          BOOLEAN       NOT NULL DEFAULT 1,
  fecha_creacion  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_producto),
  KEY idx_productos_precio (precio),
  CONSTRAINT fk_productos_categoria
    FOREIGN KEY (id_categoria) REFERENCES categorias (id_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. imagenes_producto  (se borran en cascada con su producto)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS imagenes_producto (
  id_imagen   INT NOT NULL AUTO_INCREMENT,
  id_producto INT NOT NULL,
  url         VARCHAR(255) NOT NULL,
  orden       SMALLINT     NOT NULL DEFAULT 0,
  PRIMARY KEY (id_imagen),
  CONSTRAINT fk_imagenes_producto
    FOREIGN KEY (id_producto) REFERENCES productos (id_producto)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. carritos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS carritos (
  id_carrito     INT NOT NULL AUTO_INCREMENT,
  id_usuario     INT NOT NULL,
  estado         VARCHAR(20) NOT NULL DEFAULT 'abierto',  -- abierto, convertido, abandonado
  fecha_creacion DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_carrito),
  CONSTRAINT fk_carritos_usuario
    FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. detalle_carrito  (se borra en cascada con su carrito o producto)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS detalle_carrito (
  id_detalle_carrito INT NOT NULL AUTO_INCREMENT,
  id_carrito         INT      NOT NULL,
  id_producto        INT      NOT NULL,
  cantidad           SMALLINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id_detalle_carrito),
  CONSTRAINT fk_detcarrito_carrito
    FOREIGN KEY (id_carrito)  REFERENCES carritos (id_carrito)   ON DELETE CASCADE,
  CONSTRAINT fk_detcarrito_producto
    FOREIGN KEY (id_producto) REFERENCES productos (id_producto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. cupones
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cupones (
  id_cupon       INT NOT NULL AUTO_INCREMENT,
  codigo         VARCHAR(30)   NOT NULL,
  tipo_descuento VARCHAR(15)   NOT NULL,                  -- porcentaje o valor_fijo
  valor          DECIMAL(10,2) NOT NULL,
  fecha_inicio   DATE          NOT NULL,
  fecha_fin      DATE          NOT NULL,
  activo         BOOLEAN       NOT NULL DEFAULT 1,
  PRIMARY KEY (id_cupon),
  UNIQUE KEY uq_cupones_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. pedidos
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pedidos (
  id_pedido    INT NOT NULL AUTO_INCREMENT,
  id_usuario   INT NOT NULL,
  id_direccion INT NOT NULL,
  id_cupon     INT DEFAULT NULL,                          -- opcional
  fecha_pedido DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  subtotal     DECIMAL(12,2) NOT NULL,
  descuento    DECIMAL(12,2) NOT NULL DEFAULT 0,
  costo_envio  DECIMAL(12,2) NOT NULL DEFAULT 0,
  total        DECIMAL(12,2) NOT NULL,
  estado       VARCHAR(20)   NOT NULL DEFAULT 'pendiente', -- pendiente, pagado, enviado, entregado, cancelado
  PRIMARY KEY (id_pedido),
  KEY idx_pedidos_estado (estado),
  CONSTRAINT fk_pedidos_usuario
    FOREIGN KEY (id_usuario)   REFERENCES usuarios (id_usuario),
  CONSTRAINT fk_pedidos_direccion
    FOREIGN KEY (id_direccion) REFERENCES direcciones (id_direccion),
  CONSTRAINT fk_pedidos_cupon
    FOREIGN KEY (id_cupon)     REFERENCES cupones (id_cupon)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 10. detalle_pedido  (guarda el precio del momento de la compra)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS detalle_pedido (
  id_detalle_pedido INT NOT NULL AUTO_INCREMENT,
  id_pedido         INT           NOT NULL,
  id_producto       INT           NOT NULL,
  cantidad          SMALLINT      NOT NULL DEFAULT 1,
  precio_unitario   DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id_detalle_pedido),
  CONSTRAINT fk_detpedido_pedido
    FOREIGN KEY (id_pedido)   REFERENCES pedidos (id_pedido),
  CONSTRAINT fk_detpedido_producto
    FOREIGN KEY (id_producto) REFERENCES productos (id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 11. pagos  (nunca se guardan datos de tarjeta, solo la referencia)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pagos (
  id_pago                INT NOT NULL AUTO_INCREMENT,
  id_pedido              INT           NOT NULL,
  metodo                 VARCHAR(30)   NOT NULL,          -- tarjeta, transferencia, billetera digital
  estado                 VARCHAR(20)   NOT NULL DEFAULT 'pendiente', -- pendiente, aprobado, rechazado
  referencia_transaccion VARCHAR(80)   DEFAULT NULL,
  monto                  DECIMAL(12,2) NOT NULL,
  fecha_pago             DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_pago),
  CONSTRAINT fk_pagos_pedido
    FOREIGN KEY (id_pedido) REFERENCES pedidos (id_pedido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 12. resenas
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS resenas (
  id_resena    INT NOT NULL AUTO_INCREMENT,
  id_producto  INT      NOT NULL,
  id_usuario   INT      NOT NULL,
  calificacion TINYINT  NOT NULL,
  comentario   TEXT     DEFAULT NULL,
  fecha        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_resena),
  CONSTRAINT chk_resenas_calificacion CHECK (calificacion BETWEEN 1 AND 5),
  CONSTRAINT fk_resenas_producto
    FOREIGN KEY (id_producto) REFERENCES productos (id_producto),
  CONSTRAINT fk_resenas_usuario
    FOREIGN KEY (id_usuario)  REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 13. mensajes_contacto (tabla independiente, sin relaciones)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mensajes_contacto (
  id_mensaje INT NOT NULL AUTO_INCREMENT,
  nombre     VARCHAR(80)  NOT NULL,
  correo     VARCHAR(120) NOT NULL,
  asunto     VARCHAR(120) NOT NULL,
  mensaje    TEXT         NOT NULL,
  fecha      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atendido   BOOLEAN      NOT NULL DEFAULT 0,
  PRIMARY KEY (id_mensaje)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  DATOS DE EJEMPLO  (INSERT IGNORE: no falla ni duplica si ya existen)
-- =====================================================================

-- Gamas de la tienda (sección 4.3 del análisis)
INSERT IGNORE INTO categorias (nombre, descripcion) VALUES
('Entrada',    'PCs gamer de entrada para jugar con buena relación precio y rendimiento'),
('Gama media', 'Equipos equilibrados para jugar en alta calidad gráfica'),
('Alta gama',  'Máximo rendimiento para gamers competitivos y entusiastas'),
('Streaming',  'Equipos pensados para jugar, transmitir y editar video');

-- Usuarios de prueba (contraseña: Pcs2026*)
INSERT IGNORE INTO usuarios (nombre, apellido, correo, contrasena_hash, telefono, rol) VALUES
('Laura',     'Gómez',    'laura.gomez@example.com',      '$2y$10$o3HyBWUyaYZhxu2R1Hqft.UQvvmI277YBZYb5PkncSz5C.qwdPvIS', '3001234567', 'administrador'),
('Andrés',    'Martínez', 'andres.martinez@example.com',  '$2y$10$o3HyBWUyaYZhxu2R1Hqft.UQvvmI277YBZYb5PkncSz5C.qwdPvIS', '3109876543', 'cliente'),
('Valentina', 'Rojas',    'valentina.rojas@example.com',  '$2y$10$o3HyBWUyaYZhxu2R1Hqft.UQvvmI277YBZYb5PkncSz5C.qwdPvIS', NULL,         'cliente');
