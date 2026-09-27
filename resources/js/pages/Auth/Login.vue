<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
</script>

<template>
    <Head title="Anfitrión — Amenhoot" />
    <div class="pantalla-login">
        <img
            class="fondo"
            src="/assets/img/fondo-amenhoot.jpg"
            alt=""
            width="1600"
            height="1067"
            decoding="async"
            aria-hidden="true"
        />
        <div class="velo" aria-hidden="true" />

        <p class="marca" aria-label="Amenhoot">
            <span class="marca-amen">Amen</span><span class="marca-hoot">hoot</span>
        </p>

        <div class="contenido">
            <header class="cabecera">
                <h1>Anfitrión</h1>
                <p class="subtitulo">Entra para crear y dirigir partidas</p>
            </header>

            <Form action="/login" method="post" class="formulario" #default="{ errors, processing }">
                <label class="bloque">
                    <span class="etiqueta">Email</span>
                    <input
                        v-focus
                        class="entrada"
                        type="email"
                        name="email"
                        required
                        autocomplete="username"
                        placeholder="tú@email.com"
                        aria-label="Email"
                    />
                    <p v-if="errors.email" class="error">{{ errors.email }}</p>
                </label>

                <label class="bloque">
                    <span class="etiqueta">Contraseña</span>
                    <input
                        class="entrada"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        aria-label="Contraseña"
                    />
                    <p v-if="errors.password" class="error">{{ errors.password }}</p>
                </label>

                <label class="recordar">
                    <input type="checkbox" name="remember" />
                    Recordarme
                </label>

                <button class="boton-entrar" type="submit" :disabled="processing">Entrar</button>
            </Form>
        </div>
    </div>
</template>

<style scoped>
.pantalla-login {
    position: relative;
    display: flex;
    min-height: 100dvh;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 1.5rem;
}

.fondo {
    position: absolute;
    inset: 0;
    z-index: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

.velo {
    position: absolute;
    inset: 0;
    z-index: 1;
    background:
        linear-gradient(180deg, rgb(6 11 24 / 0.5) 0%, rgb(6 11 24 / 0.78) 50%, rgb(6 11 24 / 0.92) 100%),
        radial-gradient(ellipse 70% 45% at 50% 10%, rgb(148 163 184 / 0.12), transparent);
}

.marca {
    position: absolute;
    top: max(2.25rem, calc(env(safe-area-inset-top) + 1.5rem));
    left: 0;
    right: 0;
    z-index: 3;
    margin: 0;
    font-family: var(--font-display);
    font-size: clamp(2rem, 9vw, 2.75rem);
    line-height: 1;
    letter-spacing: 0.02em;
    text-align: center;
    text-shadow: 0 3px 0 rgb(0 0 0 / 0.4);
}

.marca-amen {
    color: #f8fafc;
}

.marca-hoot {
    color: #94a3b8;
}

.contenido {
    position: relative;
    z-index: 2;
    display: flex;
    width: min(100%, 22rem);
    flex-direction: column;
    gap: 1.75rem;
}

.cabecera {
    text-align: center;
}

.cabecera h1 {
    margin: 0;
    font-family: var(--font-display);
    font-size: clamp(1.65rem, 7vw, 2.15rem);
    letter-spacing: 0.02em;
    color: #f1f5f9;
    text-shadow: 0 2px 12px rgb(0 0 0 / 0.5);
}

.subtitulo {
    margin: 0.45rem 0 0;
    font-size: 1rem;
    color: #cbd5e1;
}

.formulario {
    display: flex;
    flex-direction: column;
    gap: 1.15rem;
}

.bloque {
    display: block;
}

.etiqueta {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
    letter-spacing: 0.04em;
    color: #e2e8f0;
    text-align: center;
}

.entrada {
    display: block;
    width: 100%;
    border: 2px solid #cbd5e1;
    border-radius: 1rem;
    background: #f8fafc;
    padding: 0.95rem 1rem;
    color: #0f172a;
    text-align: center;
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.entrada::placeholder {
    color: #94a3b8;
}

.entrada:focus {
    border-color: #64748b;
    box-shadow: 0 0 0 3px rgb(100 116 139 / 0.25);
}

.error {
    margin: 0.45rem 0 0;
    font-size: 0.9rem;
    color: #fca5a5;
    text-align: center;
}

.recordar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.55rem;
    font-size: 0.95rem;
    color: #cbd5e1;
}

.boton-entrar {
    margin-top: 0.35rem;
    width: 100%;
    border: 0;
    border-radius: 999px;
    background: #f1f5f9;
    padding: 1rem 1.25rem;
    font-family: var(--font-display);
    font-size: 1.2rem;
    letter-spacing: 0.04em;
    color: #0f172a;
    cursor: pointer;
    box-shadow: 0 8px 24px rgb(0 0 0 / 0.3);
    transition: transform 0.15s ease, background 0.15s ease;
}

.boton-entrar:hover {
    background: #fff;
}

.boton-entrar:active {
    transform: scale(0.97);
}

.boton-entrar:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}
</style>
