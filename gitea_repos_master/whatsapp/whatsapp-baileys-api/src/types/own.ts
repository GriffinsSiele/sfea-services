import { Document } from 'bson/src/bson';

export declare type Session = Document;
export declare type ExistsResponse = {
  _isExists: boolean;
};

export declare type StatusResponse = {
  statusHidden: boolean;
  status?: string;
  statusSetAt?: string;
};

export declare type AvatarResponse = {
  hasAvatar: boolean;
  previewURL?: string;
  imageURL?: string;
  previewBase64?: string;
  imageBase64?: string;
  avatarHidden?: boolean;
};

export declare type BusinessResponse = {
  isBusiness: boolean;
  businessAddress?: string;
  businessDescription?: string;
  businessCategory?: string;
  businessEmail?: string;
  list__businessWebsite?: string[];
  businessTimezone?: string;
  businessSchedule?: string;
};

export declare type KeyDBRequiredFields = {
  ResultCode: string;
};

export declare type ProfileResponse = ExistsResponse &
  StatusResponse &
  AvatarResponse &
  BusinessResponse &
  KeyDBRequiredFields;
