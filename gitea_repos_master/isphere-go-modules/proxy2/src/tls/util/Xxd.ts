import logger from '../../logger'

export class Xxd {
    buffer1: Buffer
    buffer2: Buffer

    constructor(buffer1: Buffer, buffer2: Buffer) {
        this.buffer1 = buffer1
        this.buffer2 = buffer2
    }

    dump(): void {
        const byteArray1 = new Uint8Array(this.buffer1)
        const byteArray2 = new Uint8Array(this.buffer2)
        const maxLength = Math.max(byteArray1.length, byteArray2.length)

        for (let i = 0; i < maxLength; i += 16) {
            const chunk1 = byteArray1.slice(i, i + 16)
            const chunk2 = byteArray2.slice(i, i + 16)
            const hexBytes1 = Array.from(chunk1)
                .map(byte => byte.toString(16).padStart(2, '0'))
                .join(' ')
            const hexBytes2 = Array.from(chunk2)
                .map(byte => byte.toString(16).padStart(2, '0'))
                .join(' ')
            const asciiBytes1 = Array.from(chunk1)
                .map(byte => byte >= 32 && byte <= 126 ? String.fromCharCode(byte) : '.')
                .join('')
            const asciiBytes2 = Array.from(chunk2)
                .map(byte => byte >= 32 && byte <= 126 ? String.fromCharCode(byte) : '.')
                .join('')

            let line = `${ hexBytes1.padEnd(48) }  ${ asciiBytes1 } | ${ hexBytes2.padEnd(48) }  ${ asciiBytes2 }`
            if (hexBytes1 === hexBytes2) {
                logger.verbose(i.toString(16).padStart(8, '0'), line)
            } else {
                logger.warn(i.toString(16).padStart(8, '0'), line)
            }
        }
    }
}