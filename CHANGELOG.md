# Changelog

All notable changes to this extension are documented in this file.

## Removed

- Removed the legacy `StandaloneView` APIs from `TemplateBasedMailMessage`: `getMessageView()`, `setMessageView()`, `getSubjectView()`, and `setSubjectView()`.
  Configure message and subject templates through the mail-template and TypoScript configuration instead.

## Fixed

- Restored `MailUtility::getMailById()` for integrations that persist a concrete
  mail-template UID, such as TYPO3 form finishers. Editors can create multiple
  templates with the same TypoScript key; a key-based lookup could therefore select
  a different template than the one selected in the integration. `getMailByKey()`
  remains the appropriate API for intentional key-based template selection.
