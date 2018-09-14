#Architecture

The app is basically split in to MetadataExtractors/Importers
and FileExporters/Importers.

Metadata: everything which is stored in the database
- User data
- User preferences
- Shares
- Filecache
- ...

All specialized extractors are injected in to the MetaDataExtractor to create metadata.json on export.
https://github.com/owncloud/data_exporter/blob/master/lib/Exporter/MetadataExtractor.php#L31

Format of metadata.json is specified here:
https://github.com/owncloud/data_exporter/tree/master/lib/Model

Files: Everything which is stored in users home directory:
- files
- files_versions
- files_trashbin

Created by https://github.com/owncloud/data_exporter/blob/master/lib/Exporter/FilesExporter.php

Example metadata.json:
```json
{
  "date": "2018-08-23T14:18:13+00:00",
  "user": {
    "userId": "admin",
    "displayName": "admin",
    "email": null,
    "quota": "default",
    "backend": "Database",
    "enabled": true,
    "groups": [
      "admin"
    ],
    "preferences": [
      {
        "appId": "core",
        "configKey": "lang",
        "configValue": "en"
      },
      {
        "appId": "core",
        "configKey": "timezone",
        "configValue": "Europe/Berlin"
      },
      {
        "appId": "files",
        "configKey": "file_sorting",
        "configValue": "name"
      },
      {
        "appId": "files",
        "configKey": "file_sorting_direction",
        "configValue": "desc"
      }
    ],
    "files": [
      {
        "type": "folder",
        "path": "files",
        "eTag": "5b7ea6a2e03b7",
        "permissions": 31
      },
      {
        "type": "file",
        "path": "files/confidential.docx",
        "eTag": "29268a678cab030cef7febca6bcbcf1e",
        "permissions": 27
      },
      {
        "type": "folder",
        "path": "files/publicshare",
        "eTag": "5b7ea6a2de6e9",
        "permissions": 31
      },
      {
        "type": "folder",
        "path": "files_trashbin",
        "eTag": "5b7ea69543f54",
        "permissions": 31
      },
      {
        "type": "folder",
        "path": "files_trashbin/files",
        "eTag": "5b7ea69543f54",
        "permissions": 31
      },
      {
        "type": "file",
        "path": "files_trashbin/files/confidential.docx.d1534952887",
        "eTag": "825895af5aad7b2c034fd17ca3bdf31f",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/files/confidential.docx.d1534953093",
        "eTag": "6570861ae6cfb97f904c0fe4dd30dffc",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/files/confidential.docx.d1534957947",
        "eTag": "1810da6836f3bd2c6250ca31f5ad504b",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/files/confidential.docx.d1534957994",
        "eTag": "9e88b309f4e3c8cb95c0c909302c215f",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/versions/confidential.docx.v1534957616.d1534957947",
        "eTag": "400e1580f9d64d2249a8c7c544ead4c6",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/versions/confidential.docx.v1534957692.d1534957947",
        "eTag": "a4d017977adbcfe05a33cbeb68bdc895",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/versions/confidential.docx.v1534957814.d1534957947",
        "eTag": "a0460c73b6205cf10ff279322f4f0cb8",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/versions/confidential.docx.v1534957856.d1534957947",
        "eTag": "a2d5f4c5333a2f9a840adba75ecf3a1c",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/versions/confidential.docx.v1535020732.d1535020979",
        "eTag": "5b5ebc4a794ba053cc6e432269014c30",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/versions/confidential2.docx.v1535018552.d1535019101",
        "eTag": "d0ae862bb44ad2a68892243e1073b008",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/versions/confidential2.docx.v1535018888.d1535019101",
        "eTag": "12d259333695df7420d5418a11c40904",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/versions/confidential2.docx.v1535020760.d1535020982",
        "eTag": "7ff3995f7b85ac989de3313f7ef425a9",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/versions/confidential2.docx.v1535021049.d1535021791",
        "eTag": "ce24aab69f9ac15c2c506118d8ff0d34",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/versions/confidential2.docx.v1535021364.d1535021791",
        "eTag": "fa21d9d4ca3c19686db698e4c8f06e49",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/versions/confidential3.docx.v1535020789.d1535020998",
        "eTag": "5449a810f76b6b9d87c291789ee088d3",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/versions/confidential3.docx.v1535021078.d1535021791",
        "eTag": "c2deff71d3e5ceda1d4ee171db011b7b",
        "permissions": 27
      },
      {
        "type": "file",
        "path": "files_trashbin/versions/confidential3.docx.v1535021393.d1535021791",
        "eTag": "79bf2b002ca2eb35de9b43bd57b14f03",
        "permissions": 27
      },
      {
        "type": "folder",
        "path": "files_versions",
        "eTag": "5b7e92df96d32",
        "permissions": 31
      }
    ]
  }
}
```

Exported files are found in the same directory as metadata.json in the 'files' directory.



