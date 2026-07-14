/* Jeu d'icônes SVG inline (style trait, 24x24), aucune dépendance externe.
   Usage HTML : <i class="ic" data-icon="heart"></i> (remplacé au chargement)
   Usage JS   : Icons.el('heart') → élément <svg> */
(function () {
    'use strict';

    const W = (paths, fill) =>
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="' + (fill || 'none') +
        '" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        paths + '</svg>';

    const ICONS = {
        heart: W('<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>'),
        mail: W('<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>'),
        send: W('<path d="M22 2 11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/>'),
        link: W('<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>'),
        play: W('<path d="M8 5v14l11-7-11-7z"/>'),
        check: W('<path d="M20 6 9 17l-5-5"/>'),
        x: W('<path d="M18 6 6 18"/><path d="M6 6l12 12"/>'),
        refresh: W('<path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>'),
        dice: W('<rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.2" cy="8.2" r="1.1" fill="currentColor" stroke="none"/><circle cx="15.8" cy="8.2" r="1.1" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.1" fill="currentColor" stroke="none"/><circle cx="8.2" cy="15.8" r="1.1" fill="currentColor" stroke="none"/><circle cx="15.8" cy="15.8" r="1.1" fill="currentColor" stroke="none"/>'),
        logout: W('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>'),
        mars: W('<circle cx="10" cy="14" r="6"/><path d="M14.5 9.5 21 3"/><path d="M15 3h6v6"/>'),
        venus: W('<circle cx="12" cy="8" r="5.5"/><path d="M12 13.5V22"/><path d="M8.5 18.5h7"/>'),
        sparkles: W('<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15l.7 1.9 1.9.7-1.9.7L19 20.2l-.7-1.9-1.9-.7 1.9-.7L19 15z"/>'),
        trophy: W('<path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v6a5 5 0 0 1-10 0V4z"/><path d="M7 6H4a1 1 0 0 0-1 1v1a4 4 0 0 0 4 4"/><path d="M17 6h3a1 1 0 0 1 1 1v1a4 4 0 0 1-4 4"/>'),
        search: W('<circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>'),
        smile: W('<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><path d="M9 9h.01"/><path d="M15 9h.01"/>'),
        flame: W('<path d="M12 2S6 8.5 6 13a6 6 0 0 0 12 0c0-4.5-6-11-6-11z"/><path d="M12 12.5s-2 2.1-2 3.6a2 2 0 0 0 4 0c0-1.5-2-3.6-2-3.6z"/>'),
        pepper: W('<path d="M19.5 3.5c-1.8 0-3.2.9-3.9 2.4"/><path d="M15.6 5.9a3.6 3.6 0 0 1 3.9 3.6c0 5.2-6.1 10-13.6 11.4-1.5.3-2.2-1-1-1.9C10.3 15.3 12 11 12 8.5a3.6 3.6 0 0 1 3.6-2.6z"/>'),
        book: W('<path d="M2 4h6a4 4 0 0 1 4 4v12a3 3 0 0 0-3-3H2V4z"/><path d="M22 4h-6a4 4 0 0 0-4 4v12a3 3 0 0 1 3-3h7V4z"/>'),
        message: W('<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>'),
        eye: W('<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>'),
        user: W('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'),
    };

    function el(name, cls) {
        const span = document.createElement('span');
        span.className = 'ic' + (cls ? ' ' + cls : '');
        span.innerHTML = ICONS[name] || ICONS.heart;
        return span;
    }

    // Remplace les balises <i data-icon="..."> statiques du HTML.
    document.querySelectorAll('[data-icon]').forEach((node) => {
        const span = el(node.dataset.icon, node.className.replace(/\bic\b/, '').trim());
        node.replaceWith(span);
    });

    window.Icons = { el, svg: (name) => ICONS[name] || ICONS.heart };
})();
