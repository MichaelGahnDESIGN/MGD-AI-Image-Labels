#!/usr/bin/env bash
#
# Baut das veröffentlichbare Plugin-Paket ohne Entwicklungsdateien.
#
# Der Script kopiert nur explizit freigegebene Dateien in einen temporären
# Ordner. Dadurch gelangen weder Worktrees noch lokale Visualisierungen,
# Tests, Archive oder Konfigurationsgeheimnisse in das WordPress-ZIP.
# `zip -X` entfernt zusätzliche Dateisystem-Metadaten. Die Dateiliste wird
# sortiert übergeben, damit derselbe Quellstand nachvollziehbar paketiert wird.

set -euo pipefail

release_version="${1:-0.6.0}"
project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
output_dir="${project_root}/dist"
package_name="mgd-ai-image-labels-${release_version}.zip"
package_path="${output_dir}/${package_name}"
temporary_dir="$(mktemp -d "${TMPDIR:-/tmp}/mgd-ail-release.XXXXXX")"
package_root="${temporary_dir}/mgd-ai-image-labels"

cleanup() {
	# Der temporäre Ordner wird immer nur innerhalb des eben erzeugten Pfads entfernt.
	rm -rf "${temporary_dir}"
}
trap cleanup EXIT

mkdir -p "${package_root}" "${output_dir}"

# Diese Liste ist die vollständige, bewusst kleine öffentliche Paketoberfläche.
for entry in assets includes views mgd-ai-image-labels.php README.md CHANGELOG.md SECURITY.md LICENSE; do
	if [[ ! -e "${project_root}/${entry}" ]]; then
		echo "Fehlende Release-Datei: ${entry}" >&2
		exit 1
	fi

	cp -R "${project_root}/${entry}" "${package_root}/"
done

# Zusätzliche Finder-Metadaten oder Archivreste werden vor dem Packen entfernt.
find "${package_root}" -name '.DS_Store' -delete

# Einheitliche Zeitstempel machen das Archiv bei identischem Quellstand auch
# bytegenau nachvollziehbar – unabhängig vom Zeitpunkt des lokalen Checkouts.
find "${package_root}" -exec touch -t 200001010000 {} +

rm -f "${package_path}"
(
	cd "${temporary_dir}"
	find mgd-ai-image-labels -type f -print | LC_ALL=C sort | zip -X -q "${package_path}" -@
)

echo "Release-Paket erstellt: ${package_path}"
shasum -a 256 "${package_path}"
