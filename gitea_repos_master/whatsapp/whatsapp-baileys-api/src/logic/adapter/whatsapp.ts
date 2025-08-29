export class WhatsappAdapter {
  public static phoneNumberToJID(phone: string) {
    return `${phone}@c.us`;
  }
}
