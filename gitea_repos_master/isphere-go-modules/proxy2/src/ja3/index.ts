import { MessageTypeCode } from '../tls/contract/MessageType'
import { RecordTypeCode } from '../tls/contract/RecordType'
import { ClientHello } from '../tls/message/ClientHello'
import Handshake from '../tls/message/Handshake'
import Record from '../tls/message/Record'
import { ServerHello } from '../tls/message/ServerHello'
import { newJa3 } from '../tls/util/Ja3'

// @see https://tls12.xargs.org/#client-hello/annotated
// @see https://github.com/alexbers/manual-tls
export function hijack(data: Buffer): Buffer {
    const record: Record = new Record()
    record.unmarshal(data)

    if (record.type.code === RecordTypeCode.Handshake) {
        const handshake: Handshake = record.record as Handshake

        if (handshake.message.type.code === MessageTypeCode.ClientHello) {
            const clientHello: ClientHello = handshake.message.data as ClientHello
            const ja3 = newJa3(exampleJa3)

            // clientHello.clientVersion = ja3.tlsVersion
            // clientHello.cipherSuites = ja3.cipherSuites
            // console.dir(ja3.extensions)
            // return null

            // return record.marshal()
        } else if (handshake.message.type.code === MessageTypeCode.ServerHello) {
            const serverHello: ServerHello = handshake.message.data as ServerHello
            // serverHello.cipherSuite = new CipherSuite(4867)
        }
    }

    const marshalled = record.marshal()

    if (Buffer.compare(data, marshalled) !== 0) {
        // const xxd = new Xxd(data, marshalled)
        // xxd.dump()
        // console.dir({data, marshalled})
    }

    return data
}

const exampleJa3: string = '771,4865-4867-4866-49195-49199-52393-52392-49196-49200-49162-49161-49171-49172-156-157-47-53,0-23-65281-10-11-16-5-34-51-43-13-45-28-41,29-23-24-25-0-1,0'
