import { CipherSuite } from '../contract/CipherSuite'
import { TLSVersion } from '../contract/TLSVersion'

export class Ja3 {
    tlsVersion: TLSVersion
    cipherSuites: CipherSuite[]
    extensions: number[]
    ellipticCurves: number[]
    ecPointFormats: number[]
}

// @see https://medium.com/cu-cyber/impersonating-ja3-fingerprints-b9f555880e42
export function newJa3(encoded: string): Ja3 {
    const ja3 = new Ja3()

    const parts: string[] = encoded.split(",")

    const tlsVersionString: string = parts[0]
    ja3.tlsVersion = new TLSVersion(parseInt(tlsVersionString, 10))

    const cipherSuitesStrings: string[] = parts[1].split("-")
    const cipherSuites = cipherSuitesStrings.map((s: string): number => parseInt(s, 10))
    ja3.cipherSuites = []
    for (let i: number = 0; i < cipherSuites.length; i++) {
        ja3.cipherSuites[i] = new CipherSuite(cipherSuites[i])
    }

    const extensionsStrings: string[] = parts[2].split("-")
    ja3.extensions = extensionsStrings.map((s: string): number => parseInt(s, 10))

    const ellipticCurvesStrings: string[] = parts[3].split("-")
    ja3.ellipticCurves = ellipticCurvesStrings.map((s: string): number => parseInt(s, 10))

    const ecPointFormatsStrings: string[] = parts[4].split("-")
    ja3.ecPointFormats = ecPointFormatsStrings.map((s: string): number => parseInt(s, 10))

    return ja3
}