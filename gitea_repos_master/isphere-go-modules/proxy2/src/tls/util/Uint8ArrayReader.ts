export default class Uint8ArrayReader {
    buffer: Buffer
    index: number

    constructor(buffer: Buffer) {
        this.buffer = buffer
        this.index = 0
    }

    readByte(): number {
        if (this.index >= this.buffer.length) {
            throw new Error("End of buffer reached")
        }

        return this.buffer[this.index++]
    }

    readUint16BE(): number {
        return this.readBytes(2).readUInt16BE(0)
    }

    readBytes(length: number): Buffer {
        const endIndex: number = this.index + length
        if (endIndex > this.buffer.length) {
            throw new Error("End of buffer reached")
        }

        const bytes: Buffer = this.buffer.slice(this.index, endIndex)
        this.index = endIndex

        return bytes
    }

    readBytesWithUint8PrefixedLength(): Buffer {
        const length = this.readByte()
        return this.readBytes(length)
    }

    readBytesWithUint16BEPrefixedLength(): Buffer {
        const length = this.readUint16BE()
        return this.readBytes(length)
    }

    readTail(): Buffer {
        return this.buffer.slice(this.index)
    }

    hasMoreData(): boolean {
        return this.index < this.buffer.length
    }
}
