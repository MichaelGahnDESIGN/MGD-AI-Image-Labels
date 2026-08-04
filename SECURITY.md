# Sicherheitsrichtlinie

Bitte melde Sicherheitslücken nicht öffentlich in einem GitHub-Issue. Sende eine kurze Beschreibung, eine reproduzierbare Testanleitung und die betroffene Version vertraulich an Michael Gahn DESIGN.

Keine Zugangsdaten, Tokens, vollständigen Datenbankauszüge oder personenbezogenen Daten mitsenden.

## GitHub-Updates

Das Plugin verwendet für Update-Hinweise ausschließlich die öffentliche GitHub-Release-API des eigenen Repositories. Es enthält keine GitHub-Tokens und sendet keine Medien-, Besucher- oder Nutzerdaten. Vor einem Update werden Versionsnummer, Download-Domain und Name der Release-ZIP geprüft. Eine nicht passende oder nicht erreichbare Antwort führt zu keinem Update-Hinweis.

## Branding und Detailansicht

Icon, Banner und die optionale Dokumentations-Animation liegen vollständig im Plugin-Paket. Im WordPress-Backend werden dafür keine Fremdhosts, Webfonts, Analysewerkzeuge oder Drittanbieter-Skripte nachgeladen. Die Detailansicht enthält ausschließlich statische, lokal ausgelieferte Hilfetexte.

Die wenigen externen Service-Links führen nur auf die Website, den Support, das öffentliche Repository und das öffentliche Wiki von Michael Gahn DESIGN. Sie öffnen in einem separaten Tab mit `noopener noreferrer`, damit der neue Tab keinen Zugriff auf das aufrufende WordPress-Backend erhält.
