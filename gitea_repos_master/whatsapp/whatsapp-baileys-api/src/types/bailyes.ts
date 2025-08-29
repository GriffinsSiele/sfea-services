import { WABusinessHoursConfig } from '@whiskeysockets/baileys/lib/Types';
import makeWASocket, { makeInMemoryStore } from '@whiskeysockets/baileys';

export declare type WABusinessProfileBusinessHours = {
  timezone?: string;
  config?: WABusinessHoursConfig[];
  business_config?: WABusinessHoursConfig[];
};

export declare type Socket = ReturnType<typeof makeWASocket>;

export declare type Store = ReturnType<typeof makeInMemoryStore>;

export declare type ImageFormat = 'preview' | 'image';
