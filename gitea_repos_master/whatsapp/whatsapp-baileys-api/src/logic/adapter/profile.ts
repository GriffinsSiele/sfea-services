import { ProfileResponse } from 'own';

export class ProfileAdapter {
  public static cast(data: ProfileResponse) {
    if (data._isExists) {
      data['ResultCode'] = 'FOUND';
    }
    data._isExists = ProfileAdapter.bool(data._isExists);
    data.isBusiness = ProfileAdapter.bool(data.isBusiness);
    data.statusHidden = ProfileAdapter.bool(data.statusHidden);
    data.avatarHidden = ProfileAdapter.bool(data.avatarHidden);
    data.hasAvatar = ProfileAdapter.bool(data.hasAvatar);
    return data;
  }

  public static bool(v: any) {
    if (v === true) {
      return 'Да';
    }
    return v === false ? 'Нет' : v;
  }
}
