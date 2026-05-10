# NAS Storage And MySQL Backup

This feature is designed for a VPS + NAS deployment.

## What Is Stored Where

- Image files are not stored in MySQL.
- Image files are stored by the local storage strategy under `{storage_base_path}/uploads`.
- MySQL stores metadata: users, groups, settings, albums, image records, paths, hashes, storage strategies, and optimized image paths.
- MySQL live data should stay on the VPS local disk.
- MySQL backup files can be stored on NAS under `{storage_base_path}/backups/mysql`.

## Directory Layout

If the storage base path is:

```text
/mnt/nas/lskypro
```

Lsky Pro uses:

```text
/mnt/nas/lskypro/uploads
/mnt/nas/lskypro/backups/mysql
/mnt/nas/lskypro/imports
```

## Backup Logic

- The backup is a full MySQL SQL dump compressed as `.sql.gz`.
- It does not include image files.
- Retention keeps the newest N backup files in the current backup directory.
- If the storage base path changes, the retention policy applies to the new backup directory. Old directories are not automatically cleaned.

## Migration Flow

Old VPS:

1. Enable maintenance mode or otherwise stop writes.
2. Run "Back up MySQL now".
3. Confirm the `.sql.gz` exists in `backups/mysql`.

New VPS:

1. Mount the NAS to the target path, for example `/mnt/nas/lskypro`.
2. Deploy Lsky Pro.
3. In admin settings, enter the migration storage base path.
4. Click "Apply migration storage path".
5. Upload or download the `.sql.gz` backup file.
6. Restore MySQL with a controlled restore flow or manually through MySQL tools.

Applying the storage base path updates Lsky Pro configuration immediately. It does not require restarting MySQL. If Docker volume mounts are changed, restart the Docker services so the new mount is visible inside containers.

## NAS Image Import

Manual files copied into NAS are not automatically visible in Lsky Pro because the app needs database records.

Use `{storage_base_path}/imports` for manual imports:

1. Copy images into the `imports` directory.
2. Open "NAS Image Import".
3. Confirm the import.

The import uses the normal upload pipeline:

- Creates image database records.
- Assigns imported images to the current administrator.
- Stores images under `{storage_base_path}/uploads`.
- Generates thumbnails and optimized share WebP files.
- Removes source files from `imports` after successful import.
- Keeps failed files in `imports` and reports the failure reason.
