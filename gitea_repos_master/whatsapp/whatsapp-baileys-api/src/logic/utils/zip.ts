import AdmZip from 'adm-zip';
import * as fs from 'fs';

export function zipFolder(sourceFolderPath: string, zipFilePath: string) {
  const zip = new AdmZip();
  zip.addLocalFolder(sourceFolderPath);
  zip.writeZip(zipFilePath);
}
export function unzipFolder(
  destinationFolderPath: string,
  zipFilePath: string
) {
  const zip = new AdmZip(zipFilePath);
  zip.extractAllTo(destinationFolderPath, true);
}

export function readBinaryFile(filePath: string): Buffer {
  return fs.readFileSync(filePath);
}

export function writeBinaryFile(data: Buffer, filePath: string) {
  return fs.writeFileSync(filePath, data, 'utf-8');
}
