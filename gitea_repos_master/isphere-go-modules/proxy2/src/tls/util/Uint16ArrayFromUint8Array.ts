export default function Uint16ArrayFromUint8Array(uint8Array: Buffer): Uint16Array {
    const uint16Array: Uint16Array = new Uint16Array(uint8Array.length / 2)
    for (let i: number = 0; i < uint8Array.length; i += 2) {
        uint16Array[i / 2] = (uint8Array[i] << 8) | uint8Array[i + 1]
    }

    return uint16Array
}

export function Uint8ArrayFromUint16Array(uint16Array: Uint16Array): Uint8Array {
    const uint8Array: Buffer = Buffer.alloc(uint16Array.length * 2)
    for (let i: number = 0; i < uint16Array.length; i++) {
        uint8Array[i * 2] = uint16Array[i] >> 8
        uint8Array[i * 2 + 1] = uint16Array[i] & 0xFF
    }

    return uint8Array
}