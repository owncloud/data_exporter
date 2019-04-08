# Data Exporter
A set of cli-tools to import and export users from one ownCloud instance
in to another. The export contains all user-settings, files and shares.

## Export Format
Note: Will have breaking changes.

## Use cases
- Manual zero-downtime migration of users and their shares from one instance in to another.
- Migrate from instances with different storages (POSIX to S3).
- Service GDPR-Requests by providing all files and metadata of a user in a single package.
- Merge users from different instances.

## Usage Example
We want to export "user1" from an "old" to a "new" instance while preserving all shares with
users on the old instance. For this example both instance must be able to reach each
other via federation. Test if you can create remote-shares before starting this process.

Export the user on the **source instance**

``$ ./occ export:user user1 /tmp/export``

This will create a folder /tmp/export/user1 which contains
all the files and metadata of the user.

Copy the export to the **target instance** for example

``$ scp -rp /tmp/export root@newinstance.com:/tmp/export``

Import the user by running the import command on the **target instance**

``$ ./occ import:user /tmp/export/user1``

This imports the user in to the **target instance** while converting all his outgoing-shares
to federated shares pointing to the **source instance**.

As user1 now lives on **both instances** you might want to migrate all shares on the **source instance** so that
they point to the **target instance**. You can run this command on the **source instance**

``$ ./occ export:migrate:share user1 https://newinstance.com``

Finally you can delete user1 on the **source instance** (This can not be undone!):

``$ ./occ user:delete user1``


## What is exported and imported?
- Files (Local)
- Meta-data (Username, Email, Personal Settings)
- Shares (Local, Link-shares, Group-Shares)
- Versions
- Trashbin (not for objectstorage)

## Known Limitations
- Comments are not exported / imported
- Tags are not exported / imported
- External-storages are not exported / imported, yet they can be mounted on a second instance
- Local users need to set a new password (WIP: link?)
- Version imports to S3 do not preserve their timestamp.
- Import alias (import using another username) currently does not work. See https://github.com/owncloud/data_exporter/issues/9
- Shares import requires federation to be correctly setup between both servers and share-api to be enabled.
- Share's state will be always "accepted" regardless of the state in the old server. (WIP: Link?)
- Remote shares from both directions need to be manually accepted. (Ticket: Link?)
- Federated shares from other servers are not migrated. (Ticket: Link?)
- Password protected link-shares are only working with the same password on the new server if the `passwordsalt` key from config.php is copied from the old server to the new one.
- Group shares require the group to be present on the target instance or else the share will be ignored silently.
- If link-shares require a password on the target server but do so on the old the import process will crash. (Ticket: Link?)

As this is an early version some limitations might be fixed in the future while others
can not be circumvented.

