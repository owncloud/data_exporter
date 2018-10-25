#Data Exporter
A set of cli-tools to import and export users from one ownCloud instance
in to another. The export contains all user-settings, files and shares.

##Use cases
- Manual zero-downtime migration of users and their shares from one instance in to another.
- Migrate from instances with different storages (POSIX to S3).
- Service GDPR-Requests by providing all files and metadata of a user in a single package.
- Merge users from different instances.

##Usage Example
We want to export "user1" from an "old" to a "new" instance while preserving all shares with
users on the old instance. For this example both instance must be able to reach each
other via federation. Test if you can create remote-shares before starting this process.

Export the user on the source instance

``$ ./occ export:user user1 /tmp/export``

This will create a folder /tmp/export/user1 which contains
all the files and metadata of the user.

Copy the export to the target instance for example

``$ scp -rp /tmp/export root@newinstance.com:/tmp/export``

Import the user by running the import command on the new instance

``$ ./occ import:user /tmp/export/user1``

This imports the user in to the new instance while converting all his outgoing-shares
to federated shares pointing to the old instance.

As user1 now lives on a new instance we need to recreate all shares so that
they point to the new instance. To do so run this command on the old instance

``$ ./occ export:migrate:share user1 https://newinstance.com``

Finally delete the user on the old instance (This can not be undone!):

``$ ./occ user:delete user1``

Note: If the user is stored in the database you need to manually reset his password (see Limitations)

##What is exported?
- Files (local)
- Meta-data (username, email, personal settings)
- Shares (Local, Link-shares, Group-Shares)
- Versions

##Known Limitations
- External-storages, comments and tags are not exported
- If a user is stored in the ownCloud database (not-ldap etc.) the password
  must be manually reset by the admin as passwords can not be migrated.
- Versions import in to S3 does not preserve the version timestamp.
- Import alias (import using another username) currently does not work and breaks share-import.
- Shares import requires federation to be correctly setup between both servers and share-api to be enabled.
- Share's state will be always "accepted" regardless of the state in the old server.
- Remote shares from both directions need to be manually accepted.
- Federated shares from other servers are not migrated.
- Password protected link-shares are not imported correctly, user needs to reset the password.
- Group shares require the group to be present on the target-system or else the share will be ignored silently.
- If link-shares require a password on the new server but do so on the old the import process will crash.

As this is an early version some limitations might be fixed in the future while others
can not be circumvented.
