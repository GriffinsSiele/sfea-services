export default interface Unmarshaller {
    unmarshal(data: Buffer): void
}
