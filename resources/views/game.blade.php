<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#3d1330">
    <title>Tu me connais ? — Jeu de couple à distance</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23e0447c'%3E%3Cpath d='M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z'/%3E%3C/svg%3E">
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
</head>
<body>
<div id="app">

    {{-- Accueil : créer ou rejoindre --}}
    <section id="screen-home" class="screen">
        <header class="hero">
            <i class="ic hero-icon" data-icon="heart"></i>
            <h1>Tu me connais&nbsp;?</h1>
            <p>Le jeu de couple à distance : devinez les réponses de l'autre et prouvez que vous vous connaissez par cœur.</p>
        </header>

        <div class="card">
            <h2>Créer une partie</h2>
            <input id="create-name" type="text" maxlength="30" placeholder="Ton prénom" autocomplete="given-name">
            <div class="row gender-row" data-group="create">
                <button type="button" class="btn chip gender-choice" data-gender="m"><i class="ic" data-icon="mars"></i> Homme</button>
                <button type="button" class="btn chip gender-choice" data-gender="f"><i class="ic" data-icon="venus"></i> Femme</button>
            </div>
            <button id="btn-create" class="btn primary"><i class="ic" data-icon="mail"></i> Créer le salon</button>
        </div>

        <div class="card">
            <h2>Rejoindre</h2>
            <input id="join-name" type="text" maxlength="30" placeholder="Ton prénom" autocomplete="given-name">
            <div class="row gender-row" data-group="join">
                <button type="button" class="btn chip gender-choice" data-gender="m"><i class="ic" data-icon="mars"></i> Homme</button>
                <button type="button" class="btn chip gender-choice" data-gender="f"><i class="ic" data-icon="venus"></i> Femme</button>
            </div>
            <input id="join-code" type="text" maxlength="6" placeholder="Code du salon (ex : AB2CD3)" autocapitalize="characters" autocomplete="off" spellcheck="false">
            <button id="btn-join" class="btn primary"><i class="ic" data-icon="heart"></i> Rejoindre</button>
        </div>
        <p id="home-error" class="error" hidden></p>
    </section>

    {{-- Salon d'attente + lancement de partie --}}
    <section id="screen-lobby" class="screen" hidden>
        <div class="card center">
            <h2>Salon</h2>
            <div class="room-code" id="lobby-code"></div>
            <button id="btn-share" class="btn ghost"><i class="ic" data-icon="link"></i> Copier le lien d'invitation</button>
            <p id="lobby-status" class="muted"></p>
        </div>

        <div id="lobby-start" class="card" hidden>
            <h2>Nouvelle partie</h2>
            <label class="field-label">Mode de jeu</label>
            <div class="pack-list">
                <button type="button" class="pack mode-choice selected" data-mode="guess">
                    <div class="pack-title"><i class="ic" data-icon="heart"></i> Tu me connais ?</div>
                    <div class="pack-desc">Devinez les réponses de l'autre, avec score. Pour les couples qui se connaissent.</div>
                </button>
                <button type="button" class="pack mode-choice" data-mode="discover">
                    <div class="pack-title"><i class="ic" data-icon="users"></i> Faire connaissance</div>
                    <div class="pack-desc">Vous répondez tous les deux à la même question, puis vous découvrez vos réponses. Idéal quand on vient de se rencontrer.</div>
                </button>
            </div>
            <label class="field-label">Pack de questions</label>
            <div id="pack-list" class="pack-list"></div>
            <label class="field-label">Nombre de questions</label>
            <div class="row">
                <button class="btn chip rounds-choice" data-rounds="6">6</button>
                <button class="btn chip rounds-choice selected" data-rounds="10">10</button>
                <button class="btn chip rounds-choice" data-rounds="14">14</button>
            </div>
            <label id="ai-toggle-wrap" class="ai-toggle" hidden>
                <input type="checkbox" id="ai-toggle">
                <span><i class="ic" data-icon="sparkles"></i> Questions inédites générées par IA</span>
            </label>
            <button id="btn-start" class="btn primary"><i class="ic" data-icon="play"></i> C'est parti !</button>
        </div>
    </section>

    {{-- Partie en cours --}}
    <section id="screen-game" class="screen" hidden>
        <div class="scorebar">
            <div class="score me"><span class="score-avatar" id="score-my-avatar"></span><span class="score-name" id="score-my-name"></span><span class="score-val" id="score-my-val"></span></div>
            <div class="round-indicator" id="round-indicator"></div>
            <div class="score partner"><span class="score-avatar" id="score-partner-avatar"></span><span class="score-name" id="score-partner-name"></span><span class="score-val" id="score-partner-val"></span></div>
        </div>
        <div class="progress"><div class="progress-fill" id="progress-fill"></div></div>

        <div class="card">
            <p class="round-role" id="round-role"></p>
            <p class="question" id="round-question"></p>

            <div id="answer-zone">
                <textarea id="answer-input" maxlength="500" rows="3" placeholder="Ta réponse..."></textarea>
                <button id="btn-answer" class="btn primary"><i class="ic" data-icon="send"></i> Envoyer</button>
            </div>

            <div id="waiting-zone" hidden>
                <div class="pulse"><i class="ic pulse-icon" data-icon="message"></i></div>
                <p class="muted center-text" id="waiting-text"></p>
            </div>

            <div id="reveal-zone" hidden>
                <div class="reveal-block">
                    <p class="reveal-label" id="reveal-truth-label"></p>
                    <p class="reveal-answer" id="reveal-truth"></p>
                </div>
                <div class="reveal-block">
                    <p class="reveal-label" id="reveal-guess-label"></p>
                    <p class="reveal-answer" id="reveal-guess"></p>
                </div>
                <div id="validate-zone" hidden>
                    <p class="muted">Alors, ta moitié a-t-elle bien deviné ?</p>
                    <div class="row">
                        <button id="btn-correct" class="btn success"><i class="ic" data-icon="check"></i> C'est ça !</button>
                        <button id="btn-wrong" class="btn danger"><i class="ic" data-icon="x"></i> Raté</button>
                    </div>
                </div>
                <p id="wait-validate" class="muted center-text" hidden><i class="ic" data-icon="eye"></i> En attente du verdict de ta moitié...</p>
                <div id="next-zone" hidden>
                    <button id="btn-next" class="btn primary"><i class="ic" data-icon="arrow"></i> Question suivante</button>
                </div>
            </div>
        </div>
    </section>

    {{-- Fin de partie --}}
    <section id="screen-end" class="screen" hidden>
        <div class="card center">
            <span class="hero-icon end-icon" id="end-icon"></span>
            <h2 id="end-title"></h2>
            <p class="muted" id="end-score"></p>
            <div class="compat">
                <div class="compat-bar"><div class="compat-fill" id="compat-fill"></div></div>
                <p class="compat-text" id="compat-text"></p>
            </div>
            <button id="btn-replay" class="btn primary"><i class="ic" data-icon="refresh"></i> Rejouer</button>
        </div>
        <div class="card">
            <h2>Récap de la partie</h2>
            <div id="recap-list"></div>
        </div>
    </section>

    {{-- Question du jour --}}
    <section id="screen-daily" class="screen" hidden>
        <div class="card">
            <h2><i class="ic" data-icon="mail"></i> Question du jour</h2>
            <p class="question" id="daily-question"></p>

            <div id="daily-answer-zone">
                <textarea id="daily-input" maxlength="1000" rows="3" placeholder="Ta réponse (elle restera cachée jusqu'à ce que vous ayez répondu tous les deux)..."></textarea>
                <button id="btn-daily" class="btn primary"><i class="ic" data-icon="send"></i> Envoyer</button>
            </div>

            <div id="daily-wait" hidden>
                <div class="reveal-block">
                    <p class="reveal-label">Ta réponse</p>
                    <p class="reveal-answer" id="daily-mine"></p>
                </div>
                <p class="muted center-text" id="daily-wait-text"></p>
            </div>

            <div id="daily-revealed" hidden>
                <div class="reveal-block">
                    <p class="reveal-label">Ta réponse</p>
                    <p class="reveal-answer" id="daily-mine2"></p>
                </div>
                <div class="reveal-block">
                    <p class="reveal-label" id="daily-partner-label"></p>
                    <p class="reveal-answer" id="daily-partner"></p>
                </div>
            </div>
        </div>

        <div class="card" id="daily-history-card" hidden>
            <h2><i class="ic" data-icon="book"></i> Votre journal</h2>
            <div id="daily-history"></div>
        </div>
    </section>

    {{-- Barre d'onglets (visible dans un salon) --}}
    <nav id="tabbar" hidden>
        <button id="tab-game" class="tab selected"><i class="ic" data-icon="dice"></i> Jeu</button>
        <button id="tab-daily" class="tab"><i class="ic" data-icon="mail"></i> Question du jour</button>
        <button id="tab-quit" class="tab"><i class="ic" data-icon="logout"></i> Quitter</button>
    </nav>

    <div id="toast" hidden></div>
</div>

<script src="{{ asset('assets/icons.js') }}"></script>
<script src="{{ asset('assets/app.js') }}"></script>
</body>
</html>
