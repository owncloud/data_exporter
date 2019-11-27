# obj2localstore
Moves a flat directory of exported files in to their respective localstorage-structure
using exported metadata (files.jsonl)

## Example
Export metadata of one or multiple users:
```
$ occ instance:export:user --no-files --with-file-ids admin /var/export
$ occ instance:export:user --no-files --with-file-ids user1 /var/export  
```

Download all files from a swift-objectstore owncloud-bucket:
```
$ pwd
/var/owncloud_objects
$ swift --os-auth-url http://localhost:8080/v2.0 \
                       --os-tenant-name AUTH_test \
                       --os-username test \ 
                       --os-password test \ 
                       \ download  --all --object-threads=8

owncloud/urn:oid:123 [auth 0.126s, headers 0.141s, total 0.142s, 0.243 MB/s]
owncloud/urn:oid:110 [auth 0.000s, headers 0.012s, total 0.012s, 0.055 MB/s]
owncloud/urn:oid:119 [auth 0.000s, headers 0.013s, total 0.014s, 15.158 MB/s]
...
```
This results in an export-directory with meta-data and a flat directory with all file-object of the instance
named like the file-id:
```
$ tree /var/export
/var/export
├── admin
│   ├── files.jsonl
│   ├── shares.jsonl
│   └── user.json
└── user1
    ├── files.jsonl
    ├── shares.jsonl
    └── user.json

$ tree /var/owncloud_objects
owncloud_objects
├── urn:oid:123
├── urn:oid:110
├── urn:oid:119
|-- ...
```


Invoke obj2localstore to move the files in to it respective user-dir inside the /files directory of the export to replicate
owncloud localstorage-layout
```
~/c/c/a/d/t/obj2localstore:λ ./obj2localstore /var/owncloud_objects /var/export
2019/11/27 18:22:49 Creating userdir for /var/export/admin/files.jsonl
2019/11/27 18:22:49 Created Directory: /var/export/admin/files
2019/11/27 18:22:49 Created Directory: /var/export/admin/files/Documents
2019/11/27 18:22:49 Moved /var/owncloud_objects/owncloud/urn:oid:107 to /var/export/admin/files/Documents/AnotherTest.txt
2019/11/27 18:22:49 Moved /var/owncloud_objects/owncloud/urn:oid:99 to /var/export/admin/files/Documents/SINAG.txt
2019/11/27 18:22:49 Creating userdir for /var/export/user1/files.jsonl
2019/11/27 18:22:49 Created Directory: /var/export/user1/files
2019/11/27 18:22:49 Created Directory: /var/export/user1/files/Documents
2019/11/27 18:22:49 Moved /var/owncloud_objects/owncloud/urn:oid:115 to /var/export/user1/files/Documents/Example.odt
2019/11/27 18:22:49 Moved /var/owncloud_objects/owncloud/urn:oid:120 to /var/export/user1/files/Documents/sU.txt
2019/11/27 18:22:49 Created Directory: /var/export/user1/files/Photos
2019/11/27 18:22:49 Moved /var/owncloud_objects/owncloud/urn:oid:117 to /var/export/user1/files/Photos/Paris.jpg
2019/11/27 18:22:49 Moved /var/owncloud_objects/owncloud/urn:oid:119 to /var/export/user1/files/Photos/San Francisco.jpg
2019/11/27 18:22:49 Moved /var/owncloud_objects/owncloud/urn:oid:118 to /var/export/user1/files/Photos/Squirrel.jpg
```

A rollback.txt with shell move operations is created to reverse all moves if something goes wrong. 

```
$ cat rollback.txt 
mv /var/export/admin/files/Documents/AnotherTest.txt /var/owncloud_objects/owncloud/urn:oid:107
mv /var/export/admin/files/Documents/SINAG.txt /var/owncloud_objects/owncloud/urn:oid:99
mv /var/export/user1/files/Documents/Example.odt /var/owncloud_objects/owncloud/urn:oid:115
mv /var/export/user1/files/Documents/sU.txt /var/owncloud_objects/owncloud/urn:oid:120
mv /var/export/user1/files/Photos/Paris.jpg /var/owncloud_objects/owncloud/urn:oid:117
mv /var/export/user1/files/Photos/San Francisco.jpg /var/owncloud_objects/owncloud/urn:oid:119
mv /var/export/user1/files/Photos/Squirrel.jpg /var/owncloud_objects/owncloud/urn:oid:118
mv /var/export/admin/files/Documents/AnotherTest.txt /var/owncloud_objects/owncloud/urn:oid:107
mv /var/export/admin/files/Documents/SINAG.txt /var/owncloud_objects/owncloud/urn:oid:99
mv /var/export/user1/files/Documents/Example.odt /var/owncloud_objects/owncloud/urn:oid:115
mv /var/export/user1/files/Documents/sU.txt /var/owncloud_objects/owncloud/urn:oid:120
mv /var/export/user1/files/Photos/Paris.jpg /var/owncloud_objects/owncloud/urn:oid:117
mv /var/export/user1/files/Photos/San Francisco.jpg /var/owncloud_objects/owncloud/urn:oid:119
mv /var/export/user1/files/Photos/Squirrel.jpg /var/owncloud_objects/owncloud/urn:oid:118
```