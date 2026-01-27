# 🖋️ CipherPost
### *Verschlüsselte Erinnerungen für die Ewigkeit.*

**CipherPost** ist eine moderne Web-Applikation zur digitalen Archivierung von Postkarten. Sie kombiniert das nostalgische Gefühl physischer Reiseerinnerungen mit modernster End-to-End-Verschlüsselung. Jede Karte wird sicher versiegelt und kann nur mit einem einzigartigen, privat generierten Schlüssel wieder zum Leben erweckt werden.

---

## ✨ Features

* **End-to-End Verschlüsselung:** Deine Bilder werden serverseitig via PHP/OpenSSL verschlüsselt, bevor sie in der Datenbank gespeichert werden.
* **Digitaler Schlüssel (.json):** Beim Erstellen einer Karte erhältst du eine Schlüsseldatei. Ohne diese Datei bleibt der Inhalt für immer verborgen – sogar für die Datenbank-Administratoren.
* **Interaktives Design:** Ein haptisches Erlebnis mit 3D-Flip-Animationen, Papier-Texturen und flüssigen Übergängen im "Modern Vintage"-Stil.
* **Geotagging:** Halte den exakten Fundort deiner Erinnerung auf einer interaktiven Weltkarte (Leaflet.js) fest.
* **Privacy First:** Keine Nutzerkonten nötig. Deine Privatsphäre wird durch asymmetrische Kryptographie geschützt.

---

## 🛠️ Technologie-Stack

| Bereich | Technologie |
| :--- | :--- |
| **Frontend** | HTML5, Tailwind CSS, Leaflet.js (Karten) |
| **Backend** | PHP 8.x |
| **Verschlüsselung** | OpenSSL (RSA/AES Hybrid-Verfahren möglich) |
| **Datenbank** | MySQL / MariaDB |
| **Fonts** | Playfair Display (Serif), Caveat (Handschrift) |

---

## 🚀 Installation & Setup

### Voraussetzungen
* Webserver (Apache mit `mod_rewrite` oder Nginx)
* PHP 8.0+ mit der Erweiterung `extension=openssl`
* Composer für das Autoloading der Namespaces

### Schritte
1.  **Repository klonen:**
    ```bash
    git clone [https://github.com/dein-username/CipherPost.git](https://github.com/dein-username/CipherPost.git)
    cd CipherPost
    ```

2.  **Abhängigkeiten installieren:**
    ```bash
    composer install
    ```

3.  **Datenbank einrichten:**
    Erstelle eine MySQL-Datenbank und lege die Tabelle `postcards` an (Struktur siehe Dokumentation).

4.  **Dateiberechtigungen:**
    Stelle sicher, dass der Webserver Schreibrechte für eventuelle temporäre Upload-Verzeichnisse hat.

---

## 📖 Bedienungsanleitung

1.  **Erstellen:** Lade ein Motiv (Vorderseite) und einen Text/Stempel (Rückseite) hoch. Markiere optional den Ort auf der Karte.
2.  **Versiegeln:** Beim Klick auf "Archivieren" wird ein asymmetrisches Schlüsselpaar generiert.
3.  **Sichern:** Lade die `.json`-Datei herunter. **Wichtig:** Verlierst du diese Datei, ist die Postkarte unwiederbringlich verschlüsselt.
4.  **Betrachten:** Gehe auf "Archiv öffnen", lade deinen Schlüssel hoch und die Karte wird im Browser entschlüsselt und gerendert.

---

## 🔒 Sicherheit

Dieses Projekt nutzt das Prinzip der **Knowledge-Limited-Architecture**. Der private Schlüssel wird generiert und sofort an den Client zum Download gesendet. Er wird *nicht* permanent auf dem Server gespeichert. 

---

## 📜 Lizenz

Dieses Projekt ist unter der MIT-Lizenz lizenziert - siehe die [LICENSE](LICENSE) Datei für Details.

---

**CipherPost** – *Erinnerungen, die nur dir gehören.*