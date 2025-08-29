import Unmarshaller from '../tls/serializer/Unmarshaller'

export function unmarshalType<T extends Unmarshaller>(type: (new () => T), buffer: Buffer): T {
    const t: T = new type
    t.unmarshal(buffer)
    return t
}