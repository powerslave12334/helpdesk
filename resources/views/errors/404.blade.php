<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 · HelpDesk</title>
    <style>
        :root {
            --bg-dark: #090d13;
            --bg-card: rgba(17, 24, 39, 0.78);
            --border-color: rgba(148, 163, 184, 0.18);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-amber: #f59e0b;
            --accent-amber-light: #fbbf24;
            --accent-amber-soft: rgba(245, 158, 11, 0.16);
            --accent-amber-hover: #d97706;
            --accent-pink: #ff0385;
            --accent-pink-soft: rgba(255, 3, 133, 0.18);
            --grass-main: #10b981;
            --grass-dark: #064e3b;
            --grass-light: #34d399;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
            padding: 1.5rem;
            position: relative;
        }

        /* =========================================
           BACKGROUND ESPACIAL + GRID TÁCTICO
           ========================================= */
        .space-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
            background-color: var(--bg-dark);
            background-image:
                radial-gradient(circle at 74% 24%, var(--accent-pink-soft), transparent 36%),
                radial-gradient(circle at 18% 78%, var(--accent-amber-soft), transparent 42%),
                radial-gradient(circle at 55% 60%, rgba(16, 185, 129, 0.07), transparent 45%),
                linear-gradient(to right, rgba(245, 158, 11, 0.045) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(245, 158, 11, 0.045) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 100% 100%, 44px 44px, 44px 44px;
        }

        .bg-star {
            position: absolute;
            width: 3px;
            height: 3px;
            border-radius: 50%;
            background: var(--accent-amber-light);
            box-shadow: 0 0 10px var(--accent-amber);
            opacity: 0.25;
            animation: bgTwinkle var(--dur, 4s) ease-in-out infinite;
            animation-delay: var(--delay, 0s);
        }

        @keyframes bgTwinkle {
            0%,
            100% {
                opacity: 0.18;
                transform: scale(0.8);
            }

            50% {
                opacity: 0.95;
                transform: scale(1.5);
            }
        }

        /* =========================================
           LAYOUT PRINCIPAL
           ========================================= */
        .error-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1280px;
            display: grid;
            grid-template-columns: 0.95fr 1.3fr;
            align-items: center;
            gap: 2.5rem;
        }

        .error-content {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .terminal-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            width: fit-content;
            background: linear-gradient(135deg, rgba(22, 27, 34, 0.92), rgba(15, 23, 42, 0.85));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: var(--accent-amber-light);
            font-family: "Fira Code", Consolas, Monaco, monospace;
            font-size: 0.82rem;
            letter-spacing: 0.03em;
            padding: 0.48rem 0.95rem;
            border-radius: 999px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
        }

        .terminal-badge .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--accent-pink);
            box-shadow: 0 0 12px var(--accent-pink);
            animation: pulseDot 1.5s ease-in-out infinite;
        }

        @keyframes pulseDot {
            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.35;
                transform: scale(0.75);
            }
        }

        .error-code {
            font-size: clamp(5.2rem, 10vw, 8.6rem);
            font-weight: 900;
            line-height: 0.94;
            letter-spacing: -0.05em;
            background: linear-gradient(135deg, var(--accent-amber-light) 10%, var(--accent-amber) 48%, var(--accent-pink) 98%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            filter: drop-shadow(0 10px 28px rgba(245, 158, 11, 0.18));
        }

        .error-title {
            font-size: clamp(1.5rem, 2.5vw, 2.15rem);
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }

        .error-description {
            font-size: 1.04rem;
            line-height: 1.68;
            color: var(--text-muted);
            max-width: 48ch;
        }

        .error-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.82rem 1.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 10px;
            text-decoration: none;
            cursor: pointer;
            transition: transform 0.22s ease, background-color 0.22s ease, border-color 0.22s ease, box-shadow 0.22s ease, color 0.22s ease;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent-amber-light), var(--accent-amber));
            color: #090d13;
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.28);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--accent-amber), var(--accent-amber-hover));
            transform: translateY(-2px);
            box-shadow: 0 8px 26px rgba(245, 158, 11, 0.42);
        }

        .btn-secondary {
            background-color: var(--bg-card);
            backdrop-filter: blur(10px);
            color: var(--text-main);
            border-color: var(--border-color);
        }

        .btn-secondary:hover {
            border-color: rgba(245, 158, 11, 0.6);
            color: var(--accent-amber-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        /* =========================================
           CONTENEDOR SVG Y ANIMACIONES
           ========================================= */
        .svg-container {
            width: 100%;
            max-width: 1080px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(180deg, rgba(17, 24, 39, 0.55) 0%, rgba(13, 17, 23, 0.78) 100%);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 1.25rem;
            box-shadow:
                0 24px 60px rgba(0, 0, 0, 0.45),
                inset 0 1px 0 rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(12px);
        }

        .svg-container svg {
            width: 100%;
            height: auto;
            display: block;
            overflow: visible;
            border-radius: 16px;
        }

        @keyframes shipy {
            0%,
            100% {
                transform: translateY(0px);
            }

            45% {
                transform: translateY(26px);
            }
        }

        @keyframes ufo {
            0% {
                transform: rotate(0deg) translateY(0px);
            }

            20% {
                transform: rotate(-5deg) translateY(-4px);
            }

            65% {
                transform: rotate(6deg) translateY(3px);
            }

            100% {
                transform: rotate(0deg) translateY(0px);
            }
        }

        @keyframes ufowin {
            0%,
            100% {
                transform: translate(0px, 0px);
            }

            50% {
                transform: translate(28px, -4px);
            }
        }

        @keyframes ufowinscale {
            0%,
            50%,
            100% {
                transform: scaleX(1);
            }

            25%,
            75% {
                transform: scaleX(1.18);
            }
        }

        @keyframes beamfront {
            0%,
            100% {
                transform: scaleX(1) skewX(0deg);
                opacity: 0.34;
            }

            12.5%,
            37.5%,
            62%,
            87.5% {
                opacity: 0.65;
            }

            45% {
                transform: scaleX(1.28) skewX(2deg) rotate(-2deg);
            }
        }

        @keyframes beamfront2 {
            0%,
            100% {
                transform: scaleX(1) skewX(0deg);
                opacity: 0.58;
            }

            12.5%,
            37.5%,
            62%,
            87.5% {
                opacity: 0.3;
            }

            45% {
                transform: scaleX(1.28) skewX(1deg) rotate(-4deg);
            }
        }

        @keyframes moonwalk {
            0% {
                transform: rotate(0deg);
            }

            15% {
                transform: rotate(-25deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes starblink {
            0% {
                transform: rotate(0deg) scale(0);
                opacity: 0;
            }

            10% {
                opacity: 1;
            }

            30% {
                transform: rotate(45deg) scale(1.25);
                opacity: 1;
            }

            55% {
                transform: rotate(90deg) scale(0);
                opacity: 0;
            }

            100% {
                opacity: 0;
                transform: rotate(90deg) scale(0);
            }
        }

        @keyframes shootingstar {
            0% {
                stroke-dashoffset: 200;
            }

            25% {
                stroke-dashoffset: 0;
            }

            50%,
            100% {
                stroke-dashoffset: -200;
            }
        }

        @keyframes shootingstar22 {
            0% {
                stroke-dashoffset: 500;
            }

            25% {
                stroke-dashoffset: 0;
            }

            50%,
            100% {
                stroke-dashoffset: -500;
            }
        }

        @keyframes shootingstar6 {
            0% {
                stroke-dashoffset: 220;
            }

            25% {
                stroke-dashoffset: 0;
            }

            50%,
            100% {
                stroke-dashoffset: -220;
            }
        }

        @keyframes clouds {
            0%,
            100% {
                transform: translateX(0px);
            }

            50% {
                transform: translateX(-45px);
            }
        }

        @keyframes float404 {
            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            35% {
                transform: translateY(-16px) rotate(-1.4deg);
            }

            70% {
                transform: translateY(-7px) rotate(1.4deg);
            }
        }

        @keyframes cowAbduct {
            0%,
            100% {
                transform: translateY(0px) rotate(0deg);
            }

            30% {
                transform: translateY(-14px) rotate(-3deg);
            }

            60% {
                transform: translateY(-24px) rotate(3deg);
            }
        }

        @keyframes tailSwing {
            0%,
            100% {
                transform: rotate(0deg);
            }

            50% {
                transform: rotate(24deg);
            }
        }

        @keyframes grassSway {
            0%,
            100% {
                transform: skewX(0deg);
            }

            50% {
                transform: skewX(3.5deg);
            }
        }

        /* Letras 404 */
        #galinv #SVG_404 {
            transform-box: fill-box;
            transform-origin: center center;
            animation: float404 3.4s ease-in-out infinite;
        }

        /* Vaca y cola */
        #galinv #COW_GROUP {
            transform-box: fill-box;
            transform-origin: center center;
            animation: cowAbduct 3.2s ease-in-out infinite;
        }

        #galinv #COW_TAIL {
            transform-box: fill-box;
            transform-origin: top left;
            animation: tailSwing 1.4s ease-in-out infinite;
        }

        /* Pasto */
        #galinv #GRASS_TUFTS {
            transform-box: fill-box;
            transform-origin: bottom center;
            animation: grassSway 2.8s ease-in-out infinite;
        }

        /* Nave y rayos */
        #galinv .shipy {
            transform-box: fill-box;
            transform-origin: center center;
            animation: shipy 2.68s ease-in-out infinite;
        }

        #galinv .ufo {
            transform-box: fill-box;
            transform-origin: center center;
            animation: ufo 2.68s ease-in-out infinite;
        }

        #galinv #Window {
            transform-box: fill-box;
            transform-origin: center center;
            animation: ufowin 2.68s ease-in-out infinite;
        }

        #galinv .winscale {
            transform-box: fill-box;
            transform-origin: center center;
            animation: ufowinscale 2.68s ease-in-out infinite;
        }

        #galinv .beams {
            transform-box: fill-box;
            transform-origin: top center;
            animation: beamfront 2.68s ease-in-out infinite;
        }

        #galinv .beams2 {
            transform-box: fill-box;
            transform-origin: top center;
            animation: beamfront2 2.68s ease-in-out infinite;
        }

        /* Luna */
        #galinv .themoon {
            transform-box: fill-box;
            transform-origin: center center;
            animation: moonwalk 4s ease-in-out infinite;
        }

        /* Estrellas */
        #galinv .star1,
        #galinv .star2,
        #galinv .star3,
        #galinv .star4,
        #galinv .star5,
        #galinv .star6 {
            transform-box: fill-box;
            transform-origin: center center;
            animation: starblink 2.64s ease-in-out infinite;
            opacity: 0;
        }

        #galinv .star1 { animation-delay: 0s; }
        #galinv .star2 { animation-delay: 0.28s; }
        #galinv .star3 { animation-delay: 0.84s; }
        #galinv .star4 { animation-delay: 1.36s; }
        #galinv .star5 { animation-delay: 1.64s; }
        #galinv .star6 { animation-delay: 2.16s; }

        /* Láseres */
        #galinv [id^="LaserLine_"] {
            animation-name: shootingstar;
            animation-duration: 2.64s;
            animation-timing-function: linear;
            animation-iteration-count: infinite;
            stroke-dasharray: 200;
            stroke-dashoffset: 200;
        }

        #galinv #LaserLine_1 { animation-delay: 0.12s; }
        #galinv #LaserLine_2 { animation-delay: 0.24s; }
        #galinv #LaserLine_3 { animation-delay: 0.36s; }
        #galinv #LaserLine_4 { animation-delay: 0.48s; }
        #galinv #LaserLine_5 { animation-delay: 0.60s; }

        #galinv #LaserLine_6 {
            animation-name: shootingstar6;
            stroke-dasharray: 220;
            stroke-dashoffset: 220;
            animation-delay: 0.72s;
        }

        #galinv #LaserLine_7 { animation-delay: 0.84s; }
        #galinv #LaserLine_8 { animation-delay: 0.96s; }
        #galinv #LaserLine_9 { animation-delay: 1.08s; }
        #galinv #LaserLine_10 { animation-delay: 1.20s; }
        #galinv #LaserLine_11 { animation-delay: 1.32s; }
        #galinv #LaserLine_12 { animation-delay: 1.44s; }
        #galinv #LaserLine_13 { animation-delay: 1.56s; }
        #galinv #LaserLine_14 { animation-delay: 1.68s; }
        #galinv #LaserLine_15 { animation-delay: 1.80s; }
        #galinv #LaserLine_16 { animation-delay: 1.92s; }
        #galinv #LaserLine_17 { animation-delay: 2.02s; }
        #galinv #LaserLine_18 { animation-delay: 2.14s; }
        #galinv #LaserLine_19 { animation-delay: 2.26s; }
        #galinv #LaserLine_20 { animation-delay: 2.38s; }
        #galinv #LaserLine_21 { animation-delay: 2.50s; }

        #galinv #LaserLine_22 {
            animation-name: shootingstar22;
            stroke-dasharray: 500;
            stroke-dashoffset: 500;
            animation-delay: 2.64s;
        }

        #galinv #LaserLine_23 { animation-delay: 2.26s; }
        #galinv #LaserLine_24 { animation-delay: 2.64s; }

        /* Nubes */
        #galinv #Cloud1,
        #galinv #Cloud2 {
            transform-box: fill-box;
            transform-origin: center center;
            animation: clouds 3.5s ease-in-out infinite;
        }

        #galinv #Cloud2 {
            animation-delay: -1.2s;
        }

        @media (max-width: 920px) {
            .error-wrapper {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .error-content {
                align-items: center;
                order: 2;
            }

            .svg-container {
                order: 1;
                max-width: 580px;
                margin: 0 auto;
                padding: 0.9rem;
            }

            .error-actions {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="space-bg" aria-hidden="true">
        <span class="bg-star" style="top: 12%; left: 14%; --dur: 3.6s; --delay: 0.2s;"></span>
        <span class="bg-star" style="top: 22%; left: 82%; --dur: 4.5s; --delay: 1.1s;"></span>
        <span class="bg-star" style="top: 38%; left: 9%; --dur: 3.2s; --delay: 0.7s;"></span>
        <span class="bg-star" style="top: 58%; left: 88%; --dur: 5s; --delay: 1.8s;"></span>
        <span class="bg-star" style="top: 76%; left: 26%; --dur: 4.1s; --delay: 0.4s;"></span>
        <span class="bg-star" style="top: 84%; left: 74%; --dur: 3.8s; --delay: 2.2s;"></span>
        <span class="bg-star" style="top: 16%; left: 52%; --dur: 4.8s; --delay: 1.5s;"></span>
        <span class="bg-star" style="top: 66%; left: 48%; --dur: 3.5s; --delay: 0.9s;"></span>
    </div>

    <main class="error-wrapper">
        <section class="error-content">
            <div class="terminal-badge">
                <span class="dot"></span>
                <span>HTTP 404 // NOT_FOUND</span>
            </div>

            <h1 class="error-code">404</h1>
            <h2 class="error-title">¡Ups! Esta página fue abducida</h2>
            <p class="error-description">
                Parece que la ruta que buscas desapareció en el espacio exterior, fue movida o nunca existió en este
                servidor.
            </p>

            <div class="error-actions">
                <a href="/" class="btn btn-primary">Volver al inicio</a>
                <button type="button" onclick="history.back()" class="btn btn-secondary">Regresar atrás</button>
            </div>
        </section>

        <section class="svg-container" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" id="galinv" class="svgmate" viewBox="53 100 719 594">
                <defs>
                    <linearGradient id="grad404" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#FBBF24" />
                        <stop offset="50%" stop-color="#F59E0B" />
                        <stop offset="100%" stop-color="#FF0385" />
                    </linearGradient>
                    <linearGradient id="gradGrassBack" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#059669" />
                        <stop offset="100%" stop-color="#064E3B" />
                    </linearGradient>
                    <linearGradient id="gradGrass" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#10B981" />
                        <stop offset="100%" stop-color="#065F46" />
                    </linearGradient>
                    <linearGradient id="gradBeam" x1="50%" y1="0%" x2="50%" y2="100%">
                        <stop offset="0%" stop-color="#FF0385" stop-opacity="0.75" />
                        <stop offset="100%" stop-color="#FF0385" stop-opacity="0.05" />
                    </linearGradient>
                    <filter id="glow404" x="-20%" y="-20%" width="140%" height="140%">
                        <feGaussianBlur stdDeviation="8" result="blur" />
                        <feMerge>
                            <feMergeNode in="blur" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>
                </defs>

                <!-- Nubes -->
                <g id="Background_Gray" fill="#F3F3F3" opacity="0.9">
                    <path id="Cloud1" d="M217 415c-3 0-6 1-8 3a18 18 0 1 0-18 23h26c7 0 13-6 13-13s-6-13-13-13z" />
                    <path id="Cloud2"
                        d="M478 384c-3 0-6 2-7 4a12 12 0 0 0-21-6c-2-8-8-13-16-13a17 17 0 1 0 0 34h44c5 0 9-4 9-10 0-4-4-9-9-9z" />
                </g>

                <!-- Destellos y láseres -->
                <g id="EXTRAS_Bubbles">
                    <g id="Cross_1" fill="none" stroke="#F59E0B" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="2" class="star1">
                        <path d="M436 272l11 16M449 274l-16 11" />
                    </g>
                    <g id="Cross_2" fill="none" stroke="#F59E0B" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="2" class="star2">
                        <path d="M124 308l11 16M138 311l-16 10" />
                    </g>
                    <g id="Cross_3" fill="none" stroke="#F59E0B" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="2" class="star3">
                        <path d="M708 170l11 16M722 173l-16 11" />
                    </g>
                    <g id="Cross_4" fill="none" stroke="#F59E0B" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="2" class="star4">
                        <path d="M726 411l12 16M740 413l-16 11" />
                    </g>
                    <g id="Cross_5" fill="none" stroke="#F59E0B" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="2" class="star5">
                        <path d="M160 190l17 9M173 186l-9 17" />
                    </g>
                    <g id="Cross_6" fill="none" stroke="#F59E0B" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="2" class="star6">
                        <path d="M542 175h20M553 165l-1 19" />
                    </g>
                    <path id="LaserLine_1" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="2" d="M651 165l83 82" />
                    <path id="LaserLine_2" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="3" d="M230 272l84 82" />
                    <path id="LaserLine_3" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="4" d="M36 215l83 82" />
                    <path id="LaserLine_4" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="4" d="M507 137l84 82" />
                    <path id="LaserLine_5" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="4" d="M684 211l84 82" />
                    <path id="LaserLine_6" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="4" d="M363 195l129 158" />
                    <path id="LaserLine_7" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="4" d="M583 224l131 130" />
                    <path id="LaserLine_8" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="3" d="M29 442l83 82" />
                    <path id="LaserLine_9" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="4" d="M122 100l57 56" />
                    <path id="LaserLine_10" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="4" d="M223 316l101 100" />
                    <path id="LaserLine_11" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="3" d="M412 267l95 102" />
                    <path id="LaserLine_12" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="4" d="M518 178l62 61" />
                    <path id="LaserLine_13" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="4" d="M672 450l83 82" />
                    <path id="LaserLine_14" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="4" d="M255 169l131 131" />
                    <path id="LaserLine_15" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="3" d="M146 283l96 90" />
                    <path id="LaserLine_16" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="3" d="M78 527l83 82" />
                    <path id="LaserLine_17" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="2" d="M559 224l45 44" />
                    <path id="LaserLine_18" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="4" d="M363 259l84 80" />
                    <path id="LaserLine_19" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="3" d="M151 293l50 49" />
                    <path id="LaserLine_20" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="4" d="M239 146l131 130" />
                    <path id="LaserLine_21" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="3" d="M436 177l94 103" />
                    <path id="LaserLine_22" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="3" d="M14 178l225 204" />
                    <path id="LaserLine_23" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="3" d="M417 312l90 99" />
                    <path id="LaserLine_24" fill="none" stroke="#FF0385" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="4" d="M63 290l75 71" />
                </g>

                <!-- Rayo trasero -->
                <g id="Beam_Back" class="shipy">
                    <g class="beams2">
                        <path id="Beam_2_2_" fill="url(#gradBeam)" d="M304 230l-83 450 309-12-195-438z" />
                    </g>
                </g>

                <!-- Pasto en dos capas -->
                <g id="GRASS_SCENE">
                    <path d="M53 636 Q 245 614, 430 634 T 772 626 L 772 694 L 53 694 Z" fill="url(#gradGrassBack)" />
                    <path d="M53 646 Q 230 624, 415 643 T 772 635 L 772 694 L 53 694 Z" fill="url(#gradGrass)" />
                    <path d="M53 646 Q 230 624, 415 643 T 772 635" fill="none" stroke="#6EE7B7" stroke-width="2.5"
                        stroke-linecap="round" />

                    <g id="GRASS_TUFTS" fill="none" stroke="#34D399" stroke-width="2.5" stroke-linecap="round">
                        <path d="M115 644l-6-12M115 644l2-14M115 644l8-10" />
                        <path d="M225 636l-7-13M225 636l1-15M225 636l8-11" />
                        <path d="M335 638l-6-12M335 638l2-14M335 638l7-10" />
                        <path d="M590 639l-6-12M590 639l1-14M590 639l8-10" />
                        <path d="M695 636l-7-13M695 636l2-14M695 636l7-11" />
                    </g>
                </g>

                <!-- Letras 404 estilizadas -->
                <g id="SVG_404">
                    <text x="419" y="579" text-anchor="middle"
                        font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
                        font-size="166" font-weight="900" letter-spacing="-8"
                        fill="rgba(0, 0, 0, 0.42)">
                        404
                    </text>
                    <text x="415" y="575" text-anchor="middle" filter="url(#glow404)"
                        font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"
                        font-size="166" font-weight="900" letter-spacing="-8"
                        fill="url(#grad404)" stroke="#090D13" stroke-width="6" paint-order="stroke fill">
                        404
                    </text>
                </g>

                <!-- Vaca estilizada junto al pasto -->
                <g id="COW_GROUP">
                    <ellipse cx="520" cy="656" rx="48" ry="8" fill="rgba(0,0,0,0.32)" />

                    <g id="COW_TAIL">
                        <path d="M565 604c14 2 22 14 20 26" fill="none" stroke="#F8FAFC" stroke-width="3.5"
                            stroke-linecap="round" />
                        <path d="M585 628c4 3 5 8 2 11" fill="none" stroke="#161B22" stroke-width="5"
                            stroke-linecap="round" />
                    </g>

                    <rect x="546" y="624" width="10" height="28" rx="4" fill="#E5E7EB" stroke="#090D13"
                        stroke-width="2" />
                    <rect x="546" y="646" width="10" height="7" rx="2" fill="#1F2937" />

                    <rect x="524" y="626" width="10" height="28" rx="4" fill="#F9FAFB" stroke="#090D13"
                        stroke-width="2" />
                    <rect x="524" y="648" width="10" height="7" rx="2" fill="#1F2937" />

                    <rect x="492" y="592" width="76" height="40" rx="18" fill="#F9FAFB" stroke="#090D13"
                        stroke-width="2.5" />

                    <path d="M510 596c8-2 15 5 12 13-2 6-11 7-16 3-3-3-2-14 4-16z" fill="#111827" />
                    <path d="M542 604c7-3 15 3 14 11-1 6-8 10-14 7-4-2-5-15 0-18z" fill="#111827" />

                    <rect x="498" y="625" width="10" height="28" rx="4" fill="#F9FAFB" stroke="#090D13"
                        stroke-width="2" />
                    <rect x="498" y="647" width="10" height="7" rx="2" fill="#1F2937" />

                    <g id="COW_HEAD">
                        <ellipse cx="484" cy="586" rx="8" ry="4.5" transform="rotate(-18 484 586)" fill="#F9FAFB"
                            stroke="#090D13" stroke-width="2" />
                        <ellipse cx="504" cy="583" rx="8" ry="4.5" transform="rotate(18 504 583)" fill="#F9FAFB"
                            stroke="#090D13" stroke-width="2" />

                        <path d="M489 583c-2-7 1-12 5-13" fill="none" stroke="#FBBF24" stroke-width="3"
                            stroke-linecap="round" />
                        <path d="M500 582c2-7 0-12-4-13" fill="none" stroke="#FBBF24" stroke-width="3"
                            stroke-linecap="round" />

                        <rect x="478" y="582" width="32" height="32" rx="12" fill="#F9FAFB" stroke="#090D13"
                            stroke-width="2.5" />

                        <path d="M495 583c6 0 13 5 13 12-5 2-11 0-13-12z" fill="#111827" />

                        <rect x="477" y="601" width="34" height="15" rx="7" fill="#F472B6" stroke="#090D13"
                            stroke-width="2" />

                        <circle cx="487" cy="609" r="2.2" fill="#831843" />
                        <circle cx="501" cy="609" r="2.2" fill="#831843" />

                        <circle cx="486" cy="594" r="2.6" fill="#090D13" />
                        <circle cx="487" cy="593" r="0.9" fill="#FFFFFF" />
                        <circle cx="500" cy="594" r="2.6" fill="#090D13" />
                        <circle cx="501" cy="593" r="0.9" fill="#FFFFFF" />
                    </g>
                </g>

                <!-- OVNI y rayo frontal -->
                <g id="SpaceShip__x2B__BeamFront" class="shipy">
                    <g class="beams">
                        <path id="Beam_Front" fill="url(#gradBeam)" d="M551 665L335 224l-30 4 88 437z" />
                    </g>
                    <g id="Spaceahip" class="ufo">
                        <g id="Light">
                            <path id="Light_Line" fill="none" stroke="#000" stroke-linecap="round"
                                stroke-miterlimit="10" stroke-width="2" d="M309 177l2-5c2 0 3 2 4 4l-6 1z" />
                            <path id="Light_Flash_70_x25_" fill="#FF0385" d="M308 178v-1l1-4 2-2c2 0 4 2 4 5v1l-7 1z" />
                        </g>
                        <g id="Dome">
                            <g id="Underneath">
                                <path id="Underneath_Bar_White" fill="#FFF"
                                    d="M273 219s2-5 7-5l73-11c2 0 7-1 9 4l-51 6-38 6z" />
                                <path id="Underneath_Bar_1_" fill="none" stroke="#000" stroke-linecap="round"
                                    stroke-miterlimit="10" stroke-width="2" d="M273 219s2-5 7-5l73-11c2 0 7-1 9 4" />
                            </g>
                            <path id="Dome_Shadow" fill="#E2E2E2"
                                d="M320 177c-23 0-38 15-38 34v2l9-1s3-10 10-19c9-11 19-16 19-16z" />
                            <path id="Dome_Line_2_" fill="#FFF" stroke="#000" stroke-linecap="round"
                                stroke-miterlimit="10" stroke-width="2" d="M282 213v-2a35 35 0 0 1 69-8l-69 10z" />
                            <g id="Dome_Shine_Line" fill="none" stroke="#000" stroke-linecap="round"
                                stroke-miterlimit="10" stroke-width="2">
                                <path d="M288 200c4-8 11-15 19-18M287 204v-2" />
                            </g>
                        </g>
                        <g id="Saucer_1_">
                            <path id="Saucer_Line_2_" fill="#FCFCFC"
                                d="M284 217c-27 5-45 9-44 15 0 5 36 3 79-4s79-13 78-20c-1-4-38-3-81 4l-28 4" />
                            <path id="Saucer_Shadow" fill="#E2E2E2"
                                d="M397 208c-5-3-37 5-78 12-42 6-79 8-79 12s39 2 80-4c41-7 82-16 77-20z" />
                            <path id="Saucer_Line_1_" fill="none" stroke="#000" stroke-linecap="round"
                                stroke-miterlimit="10" stroke-width="2"
                                d="M284 217c-27 5-45 9-44 15 0 5 36 3 79-4s79-13 78-20c-1-4-38-3-81 4l-28 4" />
                        </g>
                        <g class="winscale">
                            <g id="Window">
                                <path id="Window_Shadow" fill="#E2E2E2"
                                    d="M293 203c0 1 1 2 2 1h6l2-2-1-3-2-2-6 1c-1 0-2 1-1 2v3z" />
                                <path id="Window_Frame" fill="none" stroke="#000" stroke-linecap="round"
                                    stroke-miterlimit="10" stroke-width="2"
                                    d="M291 205c1 2 2 3 4 2l12-2c2 0 3-1 3-3l-1-5c0-2-2-3-4-3l-11 2c-2 0-4 2-4 4l1 5z" />
                            </g>
                        </g>
                    </g>
                </g>

                <!-- Luna -->
                <g id="BACK_EXTRAS">
                    <path id="MOON" fill="#FFFDFD" stroke="#000" stroke-linecap="round" stroke-miterlimit="10"
                        stroke-width="2" d="M636 174l2 9a21 21 0 0 1-30 18 21 21 0 0 0 39-9c0-8-5-15-11-18z"
                        class="themoon" />
                </g>
            </svg>
        </section>
    </main>
</body>

</html>
