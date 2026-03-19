## Запуск

```bash
php local/export/commerceml/send.php \
  --iblockId=12 \
  --rootSectionId=55 \
  --targetUrl=https://example.com/bitrix/admin/1c_exchange.php \
  --login=exchange_user \
  --password=secret \
  --packetSize=2000 \
  --withImages=N
```

## Параметры

- `--iblockId` — ID товарного инфоблока.
- `--rootSectionId` — ID корневого раздела для экспорта.
- `--targetUrl` — URL стандартного endpoint приема `/bitrix/admin/1c_exchange.php`.
- `--login` — логин пользователя на принимающей стороне.
- `--password` — пароль пользователя на принимающей стороне.
- `--packetSize` — размер пакета в родительских товарах.
- `--withImages` — `Y` или `N`, включать ли метаданные файлов.
- `--workDir` — каталог для временных CommerceML-файлов.
- `--dryRun` — `Y` или `N`, только собрать CommerceML-файлы без отправки.

## Примечание

- Скрипт работает по штатному 1С-протоколу Bitrix.
- На принимающей стороне должен быть включен и настроен стандартный импорт каталога через `1c_exchange.php`.

## Пример dry run

```bash
php local/export/commerceml/send.php \
  --iblockId=12 \
  --rootSectionId=55 \
  --packetSize=2000 \
  --dryRun=Y
```
