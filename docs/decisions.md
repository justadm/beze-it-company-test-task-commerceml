## 2026-03-19

- Создан отдельный проект в `local/export/uralenergomash-test-task-commerceml`.
- Проект изолирован от корневого git-репозитория `b24.loc`.
- Для GitHub используется отдельный репозиторий `justadm/uralenergomash-test-task-commerceml`.
- Русское название задания сохранено в `README.md` и description репозитория.
- Для slug GitHub используется ASCII-имя без пробелов.
- Точка входа реализована как CLI-скрипт `local/export/commerceml/send.php`.
- Проверка роли инфоблока выполняется через `CCatalogSku::GetInfoByIBlock()`.
- Проверка SKU-связки выполняется через `CCatalogSku::GetInfoByProductIBlock()`.
- Отправка выполняется через `Bitrix\Main\Web\HttpClient` без `curl`.
- Финальная стратегия тестового: CommerceML + штатный `1c_exchange.php`.
- На нашей стороне реализуется только отправитель.
- На принимающей стороне используется стандартный импорт `bitrix:catalog.import.1c`.
- Ограничение 2000 применяется к родительским товарам, а SKU уходят в соответствующем `offers_XXX.xml`.
- Добавлен режим `dryRun` для локальной генерации CommerceML без сетевого обмена.
- Старый репозиторий `justadm/uralenergomash-test-task` переведен в `PRIVATE`.
- Репозиторий `justadm/uralenergomash-test-task-commerceml` используется как актуальный и переведен в `PUBLIC`.
