# Changelog

All notable changes to this extension are documented in this file.

## Removed

- Removed the legacy `StandaloneView` APIs from `TemplateBasedMailMessage`: `getMessageView()`, `setMessageView()`, `getSubjectView()`, and `setSubjectView()`.
  Configure message and subject templates through the mail-template and TypoScript configuration instead.
- Removed MailUtility::getMailById, use MailUtility::getMailByKey instead
