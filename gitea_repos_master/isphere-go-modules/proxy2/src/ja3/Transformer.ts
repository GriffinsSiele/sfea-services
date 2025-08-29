import type { TransformCallback } from 'stream'
import { Transform, TransformOptions } from 'stream'

import { hijack } from '../ja3'

export class Ja3Transform extends Transform {
    constructor(opts: TransformOptions) {
        super(opts)
    }

    _transform(chunk: any, encoding: BufferEncoding, callback: TransformCallback): void {
        const hijacked: Buffer = hijack(chunk)
        this.push(hijacked, encoding)
        callback()
    }

    _flush(callback: TransformCallback) {
        callback()
    }
}