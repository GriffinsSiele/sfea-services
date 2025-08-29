export default class Uint8ArrayWriter {
    buffer: number[]

    constructor() {
        this.buffer = []
    }

    writeByte(byte: number): void {
        this.buffer.push(byte)
    }

    writeUint16BE(value: number): void {
        const uint16: Buffer = Buffer.alloc(2)
        uint16.writeUint16BE(value)
        this.buffer.push(uint16[0])
        this.buffer.push(uint16[1])
    }

    write(buffer: Buffer): void {
        for (let i: number = 0; i < buffer.length; i++) {
            this.buffer.push(buffer[i])
        }
    }

    writeBytesWithUint8PrefixedLength(buffer: Buffer): void {
        this.writeByte(buffer.length)
        this.write(buffer)
    }

    writeBytesWithUint16BEPrefixedLength(buffer: Buffer): void {
        this.writeUint16BE(buffer.length)
        this.write(buffer)
    }

    toBuffer(): Buffer {
        return Buffer.from(this.buffer)
    }
}