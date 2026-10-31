<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

type Persona = { clave: string; nombre: string; foto: string }

const props = defineProps<{
    pinInicial?: string | null
    personas: Persona[]
}>()

const form = useForm({
    pin: props.pinInicial ?? '',
    persona: '' as string,
    equipo_id: null as number | null,
})

const modoEquipos = ref(false)
const equipos = ref<Array<{ id: number; nombre: string; color: string }>>([])
const ocupados = ref<string[]>([])

watch(
    () => form.pin,
    async (pin) => {
        if (pin.length !== 6) {
            modoEquipos.value = false
            equipos.value = []
            ocupados.value = []
            return
        }
        try {
            const respuesta = await fetch(`/api/pin/${pin}/equipos`)
            const datos = await respuesta.json()
            modoEquipos.value = Boolean(datos.modo_equipos)
            equipos.value = datos.equipos ?? []
            ocupados.value = datos.ocupados ?? []
            if (!modoEquipos.value) {
                form.equipo_id = null
            }
            if (form.persona && ocupados.value.includes(form.persona)) {
                form.persona = ''
            }
        } catch {
            modoEquipos.value = false
            ocupados.value = []
        }
    },
    { immediate: Boolean(props.pinInicial) },
)

const estaOcupada = (clave: string) => ocupados.value.includes(clave)

const personaElegida = computed(() => props.personas.find((p) => p.clave === form.persona) ?? null)

const unirse = () => form.post('/unirse')
</script>

<template>
    <Head title="Unirse — Amenhoot" />
    <div class="pantalla-unirse">
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
                <h1>¡Únete a la sala!</h1>
                <p class="subtitulo">PIN + elige tu foto</p>
            </header>

            <form class="formulario" @submit.prevent="unirse">
                <label class="bloque">
                    <span class="etiqueta">PIN</span>
                    <input
                        v-model="form.pin"
                        v-focus
                        class="entrada entrada-pin"
                        inputmode="numeric"
                        maxlength="6"
                        required
                        pattern="[0-9]{6}"
                        placeholder="000000"
                        aria-label="PIN de la partida"
                        autocomplete="one-time-code"
                    />
                    <p v-if="form.errors.pin" class="error">{{ form.errors.pin }}</p>
                </label>

                <fieldset class="personas">
                    <legend class="etiqueta">¿Quién eres?</legend>
                    <div class="rejilla" role="listbox" aria-label="Personas">
                        <button
                            v-for="persona in personas"
                            :key="persona.clave"
                            type="button"
                            class="persona"
                            role="option"
                            :aria-selected="form.persona === persona.clave"
                            :aria-label="persona.nombre"
                            :disabled="estaOcupada(persona.clave)"
                            :class="{
                                activa: form.persona === persona.clave,
                                ocupada: estaOcupada(persona.clave),
                            }"
                            @click="form.persona = persona.clave"
                        >
                            <img :src="persona.foto" :alt="persona.nombre" loading="lazy" decoding="async" />
                            <span>{{ persona.nombre }}</span>
                        </button>
                    </div>
                    <p v-if="form.errors.persona" class="error">{{ form.errors.persona }}</p>
                    <p v-else-if="personaElegida" class="aviso">Entrarás como {{ personaElegida.nombre }}</p>
                </fieldset>

                <fieldset v-if="modoEquipos" class="equipos">
                    <legend class="etiqueta">Equipo</legend>
                    <label
                        v-for="equipo in equipos"
                        :key="equipo.id"
                        class="equipo"
                        :class="{ activo: form.equipo_id === equipo.id }"
                    >
                        <input v-model="form.equipo_id" type="radio" :value="equipo.id" required />
                        <span class="punto" :style="{ background: equipo.color }" />
                        {{ equipo.nombre }}
                    </label>
                    <p v-if="form.errors.equipo_id" class="error">{{ form.errors.equipo_id }}</p>
                </fieldset>

                <button
                    class="boton-entrar"
                    type="submit"
                    :disabled="form.processing || !form.persona"
                >
                    Entrar a la sala
                </button>
            </form>
        </div>
    </div>
</template>

<style scoped>
.pantalla-unirse {
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
    width: min(100%, 24rem);
    flex-direction: column;
    gap: 1.35rem;
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
    gap: 1rem;
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
    outline: none;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.entrada:focus {
    border-color: #64748b;
    box-shadow: 0 0 0 3px rgb(100 116 139 / 0.25);
}

.entrada-pin {
    text-align: center;
    font-size: 1.85rem;
    letter-spacing: 0.35em;
}

.error {
    margin: 0.45rem 0 0;
    font-size: 0.9rem;
    color: #fca5a5;
    text-align: center;
}

.aviso {
    margin: 0.45rem 0 0;
    font-size: 0.9rem;
    color: #cbd5e1;
    text-align: center;
}

.personas,
.equipos {
    margin: 0;
    border: 0;
    padding: 0;
}

.rejilla {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 0.55rem;
}

.persona {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.3rem;
    border: 2px solid rgb(226 232 240 / 0.2);
    border-radius: 1rem;
    background: rgb(15 23 42 / 0.45);
    padding: 0.4rem 0.25rem 0.45rem;
    color: #f1f5f9;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease, transform 0.15s ease;
}

.persona img {
    width: 100%;
    aspect-ratio: 1;
    border-radius: 999px;
    object-fit: cover;
}

.persona span {
    font-size: 0.68rem;
    line-height: 1.1;
    text-align: center;
}

.persona.activa {
    border-color: #e2e8f0;
    background: rgb(248 250 252 / 0.14);
    transform: translateY(-1px);
}

.persona.ocupada {
    cursor: not-allowed;
    opacity: 0.38;
}

.equipo {
    display: flex;
    cursor: pointer;
    align-items: center;
    gap: 0.75rem;
    border: 2px solid rgb(226 232 240 / 0.25);
    border-radius: 1rem;
    background: rgb(15 23 42 / 0.45);
    padding: 0.8rem 1rem;
    color: #f1f5f9;
}

.equipo + .equipo {
    margin-top: 0.55rem;
}

.equipo.activo {
    border-color: #e2e8f0;
    background: rgb(248 250 252 / 0.12);
}

.punto {
    display: inline-block;
    width: 0.85rem;
    height: 0.85rem;
    border-radius: 999px;
}

.boton-entrar {
    margin-top: 0.2rem;
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
}

.boton-entrar:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}
</style>
