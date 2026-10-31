import normas from '../../datos/normas-amenhoot.json'

export const reglasAmenhoot = normas.reglas
export const tituloNormasAmenhoot = normas.titulo
export const cierreNormasAmenhoot = normas.cierre

export const textoNarracionNormas = (): string => {
    const partes = [`${normas.titulo}.`]
    normas.reglas.forEach((regla, indice) => {
        partes.push(`${indice + 1}. ${regla.titulo}. ${regla.texto}`)
    })
    partes.push(normas.cierre)
    return partes.join(' ')
}
