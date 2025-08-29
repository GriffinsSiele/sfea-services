import { expect } from 'chai'

import Uint8ArrayReader from '../util/Uint8ArrayReader'
import { Message, unmarshalMessage } from './Message'

describe("message/Message", (): void => {
    it("marshaller and unmarshaller should be symmetric", (): void => {
        const data: Buffer = Buffer.from([
            0xFF, // type
            0x00, // reserved
            0x00, 0x01, // payload length
            0x00, // payload
        ])

        const message: Message = unmarshalMessage(new Uint8ArrayReader(data))
        const marshalled: Buffer = message.marshal()

        expect(marshalled).to.deep.equal(data)
    })
})
