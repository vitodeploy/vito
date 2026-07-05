import { contextBridge } from 'electron';

contextBridge.exposeInMainWorld('vitoDesktop', {
  platform: process.platform,
});
