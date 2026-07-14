/* Tu me connais ? — client (polling AJAX, aucune dépendance) */
(function () {
    'use strict';

    const POLL_MS = 2500;
    const $ = (id) => document.getElementById(id);

    // --- Session (code du salon + token joueur) ---
    function loadSession() {
        try { return JSON.parse(localStorage.getItem('tmq_session')) || null; }
        catch { return null; }
    }
    function saveSession(s) { localStorage.setItem('tmq_session', JSON.stringify(s)); }
    function clearSession() { localStorage.removeItem('tmq_session'); }

    let session = loadSession();
    let state = null;        // dernier état reçu du serveur
    let viewKey = '';        // signature de l'affichage, pour ne pas écraser la saisie
    let activeTab = 'game';
    let selectedPack = 'decouverte';
    let selectedRounds = 10;
    let lastRoundNum = null; // pour le toast de verdict
    let pollTimer = null;
    let busy = false;
    const genderChoice = { create: null, join: null };

    const GENDER_ICONS = { m: 'mars', f: 'venus' };

    // Remplit un élément avec une icône suivie d'un texte.
    function setIconText(el, name, text) {
        el.replaceChildren(Icons.el(name), document.createTextNode(' ' + text));
    }

    // --- Appels API ---
    async function api(path, body) {
        const res = await fetch('api/' + path, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(body || {}),
        });
        let data = null;
        try { data = await res.json(); } catch { /* réponse vide */ }
        if (!res.ok || !data || data.ok === false) {
            const err = new Error((data && (data.error || data.message)) || 'network');
            err.status = res.status;
            throw err;
        }
        return data;
    }

    function authBody(extra) {
        return Object.assign({ code: session.code, token: session.token }, extra || {});
    }

    // --- Helpers UI ---
    const screens = ['screen-home', 'screen-lobby', 'screen-game', 'screen-end', 'screen-daily'];
    function showScreen(id) {
        screens.forEach((s) => { $(s).hidden = (s !== id); });
    }

    function showError(msg) {
        const el = $('home-error');
        el.textContent = msg;
        el.hidden = false;
        setTimeout(() => { el.hidden = true; }, 5000);
    }

    const ERRORS = {
        room_not_found: 'Salon introuvable. Vérifie le code !',
        room_full: 'Ce salon est déjà complet (2 joueurs).',
        auth_failed: 'Session invalide pour ce salon.',
        gender_missing: 'Choisis Homme ou Femme (pour accorder les questions).',
        name: 'Entre ton prénom (et le code du salon pour rejoindre) !',
        network: 'Problème de connexion, réessaie.',
    };
    const errMsg = (e) => ERRORS[e.message] || ERRORS.network;

    let toastTimer = null;
    function toast(msg, iconName) {
        const el = $('toast');
        el.replaceChildren();
        if (iconName) el.appendChild(Icons.el(iconName));
        el.appendChild(document.createTextNode(msg));
        el.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { el.hidden = true; }, 2600);
    }

    // --- Polling ---
    function startPolling() {
        stopPolling();
        pollTimer = setInterval(poll, POLL_MS);
        poll();
    }
    function stopPolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    async function poll() {
        if (!session || busy || document.hidden) return;
        try {
            state = await api('state', authBody());
            render();
        } catch (e) {
            if (e.status === 404 || e.status === 403) {
                // Salon supprimé ou token invalide : retour à l'accueil.
                clearSession();
                session = null;
                stopPolling();
                render();
            }
        }
    }

    // --- Rendu ---
    function computeViewKey() {
        if (!session) return 'home';
        if (!state) return 'loading';
        const r = state.round || {};
        const d = state.daily || {};
        return [
            activeTab, state.room.state,
            state.partner ? state.partner.name : '',
            r.num, r.status, r.my_submitted, r.other_submitted, r.i_am_target,
            state.me.score, state.partner ? state.partner.score : '',
            d.my_answer !== null, d.partner_answered, d.partner_answer !== null,
            (state.daily_history || []).length,
            state.ai_available,
        ].join('|');
    }

    function render() {
        const key = computeViewKey();
        $('tabbar').hidden = !session;

        if (!session) {
            showScreen('screen-home');
            viewKey = key;
            return;
        }
        if (!state) return; // en attente du premier état

        // Toast de verdict quand la manche vient de changer.
        const r = state.round;
        if (r && lastRoundNum !== null && r.num !== lastRoundNum && r.prev_correct !== undefined) {
            toast(
                r.prev_correct ? 'Bien deviné, +1 point !' : 'Raté pour cette fois !',
                r.prev_correct ? 'check' : 'x'
            );
        }
        if (r) lastRoundNum = r.num;

        if (key === viewKey) {
            renderScores(); // toujours rafraîchir les compteurs
            return;
        }
        viewKey = key;

        if (activeTab === 'daily') {
            renderDaily();
            showScreen('screen-daily');
        } else if (state.room.state === 'lobby') {
            renderLobby();
            showScreen('screen-lobby');
        } else if (state.room.state === 'playing') {
            renderGame();
            showScreen('screen-game');
        } else {
            renderEnd();
            showScreen('screen-end');
        }

        $('tab-game').classList.toggle('selected', activeTab === 'game');
        $('tab-daily').classList.toggle('selected', activeTab === 'daily');
    }

    function renderLobby() {
        $('lobby-code').textContent = session.code;
        const hasPartner = !!state.partner;
        $('lobby-status').textContent = hasPartner
            ? `${state.partner.name} est là ! Choisissez votre partie.`
            : 'En attente de ta moitié... Envoie-lui le code ou le lien !';
        $('lobby-start').hidden = !hasPartner;
        if (!hasPartner) return;

        // Packs
        const list = $('pack-list');
        list.innerHTML = '';
        (state.packs || []).forEach((p) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'pack' + (p.id === selectedPack ? ' selected' : '');
            b.dataset.pack = p.id;
            const title = document.createElement('div');
            title.className = 'pack-title';
            title.append(Icons.el(p.icon), document.createTextNode(' ' + p.label));
            const desc = document.createElement('div');
            desc.className = 'pack-desc';
            desc.textContent = p.desc;
            b.append(title, desc);
            b.addEventListener('click', () => {
                selectedPack = p.id;
                list.querySelectorAll('.pack').forEach((x) => x.classList.toggle('selected', x.dataset.pack === p.id));
            });
            list.appendChild(b);
        });

        $('ai-toggle-wrap').hidden = !state.ai_available;
    }

    function renderScores() {
        if (!state || !state.partner) return;
        $('score-my-avatar').replaceChildren(Icons.el(GENDER_ICONS[state.me.gender] || 'heart'));
        $('score-my-name').textContent = state.me.name;
        $('score-my-val').textContent = state.me.score;
        $('score-partner-avatar').replaceChildren(Icons.el(GENDER_ICONS[state.partner.gender] || 'heart'));
        $('score-partner-name').textContent = state.partner.name;
        $('score-partner-val').textContent = state.partner.score;
        if (state.round) {
            $('round-indicator').textContent = `Manche ${state.round.num}/${state.room.total_rounds}`;
            $('progress-fill').style.width =
                Math.round(((state.round.num - 1) / state.room.total_rounds) * 100) + '%';
        }
    }

    function renderGame() {
        renderScores();
        const r = state.round;
        if (!r) return;

        if (r.i_am_target) {
            setIconText($('round-role'), 'user', 'Question sur toi — réponds la vérité !');
        } else {
            setIconText($('round-role'), 'search', `Devine la réponse de ${r.target_name} !`);
        }
        $('round-question').textContent = r.question;

        const answering = r.status === 'answering';
        $('answer-zone').hidden = !(answering && !r.my_submitted);
        $('waiting-zone').hidden = !(answering && r.my_submitted);
        $('reveal-zone').hidden = answering;

        if (answering) {
            // Purge l'état de la manche précédente.
            $('validate-zone').hidden = true;
            $('wait-validate').hidden = true;
            if (!r.my_submitted) {
                $('answer-input').placeholder = r.i_am_target ? 'Ta vraie réponse...' : 'Ta devinette...';
            } else {
                $('waiting-text').textContent = `Réponse envoyée ! En attente de ${state.partner.name}...`;
            }
            return;
        }

        // Révélation
        $('reveal-truth-label').textContent = `La vraie réponse de ${r.target_name}`;
        $('reveal-truth').textContent = r.target_answer;
        $('reveal-guess-label').textContent = r.i_am_target
            ? `La devinette de ${state.partner.name}`
            : 'Ta devinette';
        $('reveal-guess').textContent = r.guess_answer;
        $('validate-zone').hidden = !r.i_am_target;
        $('wait-validate').hidden = r.i_am_target;
    }

    function renderEnd() {
        renderScores();
        const my = state.me.score;
        const their = state.partner ? state.partner.score : 0;
        const total = state.room.total_rounds;

        if (my === their) {
            $('end-icon').replaceChildren(Icons.el('heart'));
            $('end-title').textContent = 'Égalité parfaite !';
        } else {
            const winner = my > their ? state.me.name : state.partner.name;
            $('end-icon').replaceChildren(Icons.el('trophy'));
            $('end-title').textContent = `${winner} gagne !`;
        }
        $('end-score').textContent =
            `${state.me.name} ${my} — ${their} ${state.partner ? state.partner.name : '?'} (sur ${total} questions)`;

        // Compatibilité : part de bonnes devinettes sur la partie.
        const good = (state.recap || []).filter((x) => x.correct).length;
        const pct = total ? Math.round((good / total) * 100) : 0;
        const compatMsg =
            pct >= 90 ? 'Fusionnels ! Vous lisez dans les pensées de l\'autre' :
            pct >= 70 ? 'Sacrée complicité, vous vous connaissez par cœur' :
            pct >= 50 ? 'Belle connexion... et encore plein de choses à découvrir' :
            pct >= 30 ? 'Il reste des mystères à percer, raison de plus pour rejouer' :
                        'Vous partez de loin... mais c\'est ça qui est excitant';
        $('compat-text').textContent = `Compatibilité : ${pct}% — ${compatMsg}`;
        $('compat-fill').style.width = '0%';
        requestAnimationFrame(() => requestAnimationFrame(() => {
            $('compat-fill').style.width = pct + '%';
        }));

        const list = $('recap-list');
        list.innerHTML = '';
        (state.recap || []).forEach((item) => {
            const div = document.createElement('div');
            div.className = 'recap-item';
            const q = document.createElement('p');
            q.className = 'recap-q';
            q.textContent = `${item.num}. ${item.question}`;
            const mark = document.createElement('span');
            mark.className = 'recap-mark ' + (item.correct ? 'ok' : 'ko');
            mark.appendChild(Icons.el(item.correct ? 'check' : 'x'));
            q.prepend(mark);
            const a = document.createElement('p');
            a.className = 'recap-a';
            a.textContent = `Vraie réponse : ${item.target_answer} · Devinette : ${item.guess_answer}`;
            div.append(q, a);
            list.appendChild(div);
        });
    }

    function renderDaily() {
        const d = state.daily;
        $('daily-question').textContent = d.question;

        const answered = d.my_answer !== null;
        const revealed = d.partner_answer !== null;
        $('daily-answer-zone').hidden = answered;
        $('daily-wait').hidden = !(answered && !revealed);
        $('daily-revealed').hidden = !revealed;

        if (answered && !revealed) {
            $('daily-mine').textContent = d.my_answer;
            $('daily-wait-text').textContent = state.partner
                ? `En attente de la réponse de ${state.partner.name}...`
                : 'En attente de ta moitié...';
        }
        if (revealed) {
            $('daily-mine2').textContent = d.my_answer;
            $('daily-partner-label').textContent = `La réponse de ${state.partner.name}`;
            $('daily-partner').textContent = d.partner_answer;
        }

        const history = state.daily_history || [];
        $('daily-history-card').hidden = history.length === 0;
        const list = $('daily-history');
        list.innerHTML = '';
        history.forEach((h) => {
            const div = document.createElement('div');
            div.className = 'history-item';
            const day = document.createElement('p');
            day.className = 'history-day';
            day.textContent = new Date(h.day + 'T12:00:00').toLocaleDateString('fr-FR', {
                weekday: 'long', day: 'numeric', month: 'long',
            });
            const q = document.createElement('p');
            q.className = 'history-q';
            q.textContent = h.question;
            const mine = document.createElement('p');
            mine.className = 'recap-a';
            mine.textContent = `Toi : ${h.my_answer}`;
            const theirs = document.createElement('p');
            theirs.className = 'recap-a';
            theirs.textContent = `${state.partner ? state.partner.name : 'Ta moitié'} : ${h.partner_answer}`;
            div.append(day, q, mine, theirs);
            list.appendChild(div);
        });
    }

    // --- Actions (avec anti double-clic) ---
    async function doAction(button, fn) {
        if (busy) return;
        busy = true;
        button.disabled = true;
        try {
            await fn();
            state = await api('state', authBody());
            render();
        } catch (e) {
            if (session) toast(errMsg(e)); else showError(errMsg(e));
        } finally {
            busy = false;
            button.disabled = false;
        }
    }

    function requireGender(group) {
        const row = document.querySelector(`.gender-row[data-group="${group}"]`);
        if (!genderChoice[group]) {
            row.classList.add('missing');
            throw new Error('gender_missing');
        }
        row.classList.remove('missing');
        return genderChoice[group];
    }

    $('btn-create').addEventListener('click', () => doAction($('btn-create'), async () => {
        const name = $('create-name').value.trim();
        if (!name) throw new Error('name');
        const gender = requireGender('create');
        const res = await api('create', { name, gender });
        session = { code: res.code, token: res.token };
        saveSession(session);
        lastRoundNum = null;
        startPolling();
    }));

    $('btn-join').addEventListener('click', () => doAction($('btn-join'), async () => {
        const name = $('join-name').value.trim();
        const code = $('join-code').value.trim().toUpperCase();
        if (!name || !code) throw new Error('name');
        const gender = requireGender('join');
        const res = await api('join', { name, code, gender });
        session = { code: res.code, token: res.token };
        saveSession(session);
        lastRoundNum = null;
        startPolling();
    }));

    document.querySelectorAll('.gender-row').forEach((row) => {
        row.querySelectorAll('.gender-choice').forEach((b) => {
            b.addEventListener('click', () => {
                genderChoice[row.dataset.group] = b.dataset.gender;
                row.classList.remove('missing');
                row.querySelectorAll('.gender-choice').forEach((x) =>
                    x.classList.toggle('selected', x === b));
            });
        });
    });

    $('btn-start').addEventListener('click', () => doAction($('btn-start'), async () => {
        const useAi = state.ai_available && $('ai-toggle').checked;
        if (useAi) toast('L\'IA prépare vos questions...', 'sparkles');
        await api('start', authBody({ pack: selectedPack, rounds: selectedRounds, ai: useAi }));
        lastRoundNum = null;
    }));

    $('btn-answer').addEventListener('click', () => doAction($('btn-answer'), async () => {
        const text = $('answer-input').value.trim();
        if (!text) throw new Error('network');
        await api('answer', authBody({ text }));
        $('answer-input').value = '';
    }));

    $('btn-correct').addEventListener('click', () => doAction($('btn-correct'), () =>
        api('validate', authBody({ correct: true }))));
    $('btn-wrong').addEventListener('click', () => doAction($('btn-wrong'), () =>
        api('validate', authBody({ correct: false }))));

    $('btn-replay').addEventListener('click', () => doAction($('btn-replay'), async () => {
        await api('replay', authBody());
        lastRoundNum = null;
    }));

    $('btn-daily').addEventListener('click', () => doAction($('btn-daily'), async () => {
        const text = $('daily-input').value.trim();
        if (!text) throw new Error('network');
        await api('daily', authBody({ text }));
        $('daily-input').value = '';
    }));

    document.querySelectorAll('.rounds-choice').forEach((b) => {
        b.addEventListener('click', () => {
            selectedRounds = parseInt(b.dataset.rounds, 10);
            document.querySelectorAll('.rounds-choice').forEach((x) =>
                x.classList.toggle('selected', x === b));
        });
    });

    $('btn-share').addEventListener('click', async () => {
        const url = location.origin + location.pathname + '?c=' + session.code;
        try {
            await navigator.clipboard.writeText(url);
            toast('Lien copié !', 'link');
        } catch {
            prompt('Copie ce lien :', url);
        }
    });

    $('tab-game').addEventListener('click', () => { activeTab = 'game'; viewKey = ''; render(); });
    $('tab-daily').addEventListener('click', () => { activeTab = 'daily'; viewKey = ''; render(); });
    $('tab-quit').addEventListener('click', () => {
        if (!confirm('Quitter ce salon sur cet appareil ? (la partie reste accessible avec le code)')) return;
        clearSession();
        session = null;
        state = null;
        stopPolling();
        viewKey = '';
        render();
    });

    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

    // --- Démarrage ---
    const urlCode = new URLSearchParams(location.search).get('c');
    if (urlCode && !session) {
        $('join-code').value = urlCode.toUpperCase();
    }
    if (session) {
        startPolling();
    }
    render();
})();
