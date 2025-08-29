export default class UnitTestAdapter {
  public static removeSignatureFromAvatar(avatar: string) {
    return UnitTestAdapter.removeURLParameter(
      UnitTestAdapter.removeURLParameter(avatar, 'oe'),
      'oh'
    );
  }

  public static castResponse(response: any) {
    for (const fieldChange of ['PhotoURL', 'FullPhoto']) {
      const foundFieldIndex = response?.[0]?.findIndex(
        (f: any) => f.field === fieldChange
      );
      if (foundFieldIndex >= 0) {
        const field = response[0][foundFieldIndex];
        field.value = UnitTestAdapter.removeSignatureFromAvatar(field.value);
      }
    }

    return response;
  }

  // Reference: https://stackoverflow.com/a/1634841
  public static removeURLParameter(url: string, parameter: string) {
    const urlParts = url.split('?');
    if (urlParts.length >= 2) {
      const prefix = encodeURIComponent(parameter) + '=';
      const pars = urlParts[1].split(/[&;]/g);
      for (let i = pars.length; i-- > 0; ) {
        if (pars[i].lastIndexOf(prefix, 0) !== -1) {
          pars.splice(i, 1);
        }
      }
      return urlParts[0] + (pars.length > 0 ? '?' + pars.join('&') : '');
    }
    return url;
  }
}
