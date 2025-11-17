document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const themeToggle = document.querySelector('.theme-toggle');
    const THEME_STORAGE_KEY = 'dulces-theme-preference';

    function updateThemeToggle(theme) {
        if (!themeToggle) {
            return;
        }
        const sunIcon = themeToggle.querySelector('.icon-sun');
        const moonIcon = themeToggle.querySelector('.icon-moon');
        const label = themeToggle.querySelector('.label');
        const isDark = theme === 'dark';

        if (sunIcon && moonIcon) {
            sunIcon.hidden = isDark;
            moonIcon.hidden = !isDark;
        }
        if (label) {
            label.textContent = isDark ? 'Modo oscuro' : 'Modo claro';
        }

        themeToggle.setAttribute('aria-pressed', String(isDark));
    }

    function applyTheme(theme) {
        body.dataset.theme = theme;
        updateThemeToggle(theme);
    }

    function persistTheme(theme) {
        try {
            localStorage.setItem(THEME_STORAGE_KEY, theme);
        } catch (error) {
            console.warn('No se pudo guardar la preferencia de tema.', error);
        }
    }

    function loadThemePreference() {
        try {
            const stored = localStorage.getItem(THEME_STORAGE_KEY);
            if (stored === 'dark' || stored === 'light') {
                return stored;
            }
        } catch (error) {
            // Ignoramos errores de acceso a almacenamiento.
        }
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        return prefersDark ? 'dark' : 'light';
    }

    function setupSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
            anchor.addEventListener('click', (event) => {
                const targetId = anchor.getAttribute('href');
                if (!targetId || targetId === '#') {
                    return;
                }
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    event.preventDefault();
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    }

    /**
     * Añade la lógica para el header "sticky" en móviles.
     * Agrega una clase '.scrolled' al .top-bar cuando el usuario baja la página.
     */
    function setupStickyHeader() {
        const topBar = document.querySelector('.top-bar');
        if (!topBar) {
            return;
        }
        const scrollThreshold = 10; 

        window.addEventListener('scroll', () => {
            topBar.classList.toggle('scrolled', window.scrollY > scrollThreshold);
        }, { passive: true });
    }
/*
    function setupMobileMenu(){
        const toggleBtn = document.querySelector('.mobile-nav-toggle');
        const navMenu = document.getElementById('main-nav');
        if(!toggleBtn || !navMenu){ return; }
        toggleBtn.addEventListener('click', () => {
            //anade como quita la clase de mobile nav open al body del header
            const isOpen = document.body.classList.toggle('mobile-nav-open');
            //actualiza el estado para lectores de pantalla
            toggleBtn.setAttribute('aria-expanded', String(isOpen));
        });
        navMenu.addEventListener('click', (e) => {
            if(e.target.tagName === 'A'){
                document.body.classList.remove('mobile-nav-open');
                toggleBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }
*/

    /**
     * Observa el tamaño del header y aplica un padding-top al <main>
     * para evitar que el contenido se oculte debajo del header sticky.
     */
    /**
     * Observa el tamaño del header y aplica un padding-top al <main>
     * para evitar que el contenido se oculte debajo del header sticky.
     * --- VERSIÓN 3 (Estable, sin ResizeObserver) ---
     */
    /**
     * Observa el tamaño del header y aplica un padding-top al <main>
     * para evitar que el contenido se oculte debajo del header sticky.
     * --- VERSIÓN 4 (Estable, con 'load' event) ---
     */
    function setupHeaderPadding() {
        const topBar = document.querySelector('.top-bar');
        const main = document.querySelector('main');
        if (!topBar || !main) {
            return;
        }

        // Esta es la única función que necesitamos
        const updatePadding = () => {
            const headerHeight = topBar.offsetHeight;
            main.style.paddingTop = `${headerHeight}px`;
        };

        // 1. Ejecuta la función cuando la ventana cambie de tamaño
        window.addEventListener('resize', updatePadding);

        // 2. Ejecuta la función tan pronto el DOM está listo
        updatePadding();

        // 3. Ejecuta la función OTRA VEZ cuando toda la página
        // (incluyendo imágenes y fuentes) haya cargado.
        window.addEventListener('load', updatePadding);
    }

    /**
     * Añade la lógica para mostrar/ocultar la barra de búsqueda.
     */
    function setupSearchToggle() {
        const topBar = document.querySelector('.top-bar');
        const searchToggleBtn = document.querySelector('.search-toggle');
        const searchCancelBtn = document.getElementById('search-cancel-btn');
        const searchInput = document.getElementById('search-input');

        if (!topBar || !searchToggleBtn || !searchCancelBtn || !searchInput) {
            console.warn('Elementos de búsqueda no encontrados, la función no se activará.');
            return;
        }

        // Abrir la búsqueda
        searchToggleBtn.addEventListener('click', () => {
            topBar.classList.add('search-active');
            searchInput.focus(); 
        });

        // Cerrar la búsqueda
        searchCancelBtn.addEventListener('click', () => {
            topBar.classList.remove('search-active');
            searchInput.value = ''; // Limpiar el input
        });
    }

    const initialTheme = loadThemePreference();
    applyTheme(initialTheme);
    setupSmoothScroll();
    setupStickyHeader();
    setupHeaderPadding();
    //setupSearchToggle();
    //setupMobileMenu();

    themeToggle?.addEventListener('click', () => {
        const nextTheme = body.dataset.theme === 'dark' ? 'light' : 'dark';
        applyTheme(nextTheme);
        persistTheme(nextTheme);
    });

    const addToCartButton = document.getElementById('add-to-cart-btn');
    const cartStatus = document.getElementById('cart-feedback');
    const currentProduct = window.__CURRENT_PRODUCT__;

    if (addToCartButton && currentProduct) {
        addToCartButton.addEventListener('click', () => {
            const cartRef = window.Cart;
            if (!cartRef || typeof cartRef.addItem !== 'function') {
                if (cartStatus) {
                    cartStatus.hidden = false;
                    cartStatus.textContent = 'No se pudo acceder al carrito.';
                    cartStatus.dataset.state = 'error';
                }
                return;
            }

            try {
                cartRef.addItem(currentProduct, 1);
                if (cartStatus) {
                    cartStatus.hidden = false;
                    cartStatus.textContent = 'Producto agregado al carrito.';
                    cartStatus.dataset.state = 'success';
                }
            } catch (error) {
                console.error(error);
                if (cartStatus) {
                    cartStatus.hidden = false;
                    cartStatus.textContent = 'No se pudo agregar el producto.';
                    cartStatus.dataset.state = 'error';
                }
            }
        });
    }
});
