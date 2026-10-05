# Encounters Theme for OJS

This is a theme plugin developed for the journal Encounters in Theory and History of Education. It runs on Open Journal Systems (OJS) and aims to bring a fresher design to the journal and make the website easier to use.

## Requirements

Developed and tested with OJS 3.5.0-5.

## Features

- English, Spanish, and French interface text.
- Default Encounters logo, title, subtitle, and navigation menu.
- A homepage with a Hero image, Recent Issues, Forthcoming Articles, Announcements, and Monograph Series.

## Installation

### From GitHub

1. Download and extract the theme files from this repository.
2. Rename the extracted folder to `encounters` and place it in your OJS installation's `plugins/themes/` directory.
3. From the OJS installation directory, run:

   ```sh
   php lib/pkp/tools/installPluginVersion.php plugins/themes/encounters/version.xml
   ```

4. Log in to your journal dashboard and go to **Settings > Website > Appearance**.
5. Select **Encounters** from the list of available themes and save your changes.

## Usage

Once the theme is installed, you can change the theme settings under **Settings > Website > Appearance**.

The Encounters logo, header text, Hero text, and navigation menu have default values. You can change them in the theme settings.

### Hero Image

Upload the image under **Appearance > Setup > Homepage Image**. In the theme settings, use **Homepage image link** to choose the published issue that opens when the image is clicked.

### Forthcoming Articles

Create and publish an issue for Online First articles. In the theme settings, select it under **Forthcoming Articles source issue**. The section automatically shows the three most recently published articles in that issue.

## License

This theme is released under the GNU General Public License v3.0. See [LICENSE](LICENSE) for details.

The bundled fonts are distributed under the SIL Open Font License. Their license files are included in `fonts/`.
