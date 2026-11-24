</main>
<script>
(function () {
    var selector = document.getElementById('selector-tema');
    var guardado = localStorage.getItem('pcs-tema') || 'noche';
    document.documentElement.setAttribute('data-tema', guardado);
    if (selector) {
        selector.value = guardado;
        selector.addEventListener('change', function () {
            document.documentElement.setAttribute('data-tema', selector.value);
            localStorage.setItem('pcs-tema', selector.value);
        });
    }
})();
</script>
</body>
</html>
