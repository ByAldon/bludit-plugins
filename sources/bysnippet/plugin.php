<?php

class pluginBySnippet extends Plugin {

	private $storageFile;

	public function init()
	{
		$this->storageFile = PATH_DATABASES . 'bysnippet.json';
		$this->dbFields = array('snippets' => '[]');
	}

	private function getSnippets()
	{
		if (file_exists($this->storageFile)) {
			return json_decode(file_get_contents($this->storageFile), true) ?: array();
		}
		return json_decode($this->getValue('snippets'), true) ?: array();
	}

	private function saveSnippets($snippets) {
		file_put_contents($this->storageFile, json_encode($snippets, JSON_PRETTY_PRINT));
		$this->db['snippets'] = json_encode($snippets);
		$this->save();
	}

	public function name() { return 'BySnippet'; }

	public function description() {
		return 'Beautiful link-cards with permanent storage, auto-metadata, category management and easy copy-to-clipboard.';
	}

	private function fetchMeta($url) {
		$data = array('title' => $url, 'desc' => '');
		try {
			$options = array('http' => array('user_agent' => 'Mozilla/5.0', 'timeout' => 5));
			$context = stream_context_create($options);
			$html = @file_get_contents($url, false, $context);
			if ($html) {
				$doc = new DOMDocument();
				@$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
				$nodes = $doc->getElementsByTagName('title');
				if ($nodes->length > 0) { $data['title'] = $nodes->item(0)->nodeValue; }
				$metas = $doc->getElementsByTagName('meta');
				foreach ($metas as $meta) {
					if (in_array(strtolower($meta->getAttribute('name')), ['description', 'og:description'])) {
						$data['desc'] = $meta->getAttribute('content');
					}
				}
			}
		} catch (Exception $e) {}
		return $data;
	}

	public function post()
	{
		$snippets = $this->getSnippets();

		// Add New
		if (!empty($_POST['genUrl']) && !isset($_POST['update_snippet'])) {
			$meta = $this->fetchMeta($_POST['genUrl']);
			$cat = !empty($_POST['genCatNew']) ? $_POST['genCatNew'] : $_POST['genCatSel'];
			$snippets[] = array(
				'url'      => $_POST['genUrl'],
				'title'    => $meta['title'],
				'desc'     => $meta['desc'],
				'category' => ucfirst(strtolower($cat))
			);
		}

		// Update Snippet
		if (isset($_POST['update_snippet'])) {
			$id = $_POST['edit_id'];
			$snippets[$id]['title'] = $_POST['editTitle'];
			$snippets[$id]['desc'] = $_POST['editDesc'];
			$snippets[$id]['url'] = $_POST['editUrl'];
		}

		// Rename Category
		if (isset($_POST['rename_cat'])) {
			$old = $_POST['old_cat_name'];
			$new = ucfirst(strtolower($_POST['new_cat_name']));
			foreach ($snippets as $key => $val) {
				if ($val['category'] === $old) { $snippets[$key]['category'] = $new; }
			}
		}

		// Delete Category
		if (isset($_POST['delete_cat'])) {
			$catToDelete = $_POST['cat_to_delete'];
			$snippets = array_filter($snippets, function($item) use ($catToDelete) {
				return $item['category'] !== $catToDelete;
			});
			$snippets = array_values($snippets);
		}

		// Drag & Drop Move
		if (isset($_POST['move_id']) && isset($_POST['new_cat'])) {
			$id = $_POST['move_id'];
			$snippets[$id]['category'] = $_POST['new_cat'];
		}

		// Delete Single
		if (isset($_POST['delete_snippet'])) {
			unset($snippets[$_POST['delete_snippet']]);
			$snippets = array_values($snippets);
		}

		if (isset($_POST['nuke_all_snippets'])) {
			$snippets = array();
		}

		$this->saveSnippets($snippets);
		return true;
	}

	public function form()
	{
		$snippets = $this->getSnippets();
		$categories = array_unique(array_column($snippets, 'category'));
		sort($categories);
		if (empty($categories)) { $categories = ['General']; }

		$html = '<div class="mb-4 p-3 border rounded bg-light">
					<h5>Add New Snippet</h5>
					<div class="row">
						<div class="col-md-4"><label>URL</label><input name="genUrl" type="text" class="form-control" placeholder="https://..."></div>
						<div class="col-md-4">
							<label>Select Category</label>
							<select name="genCatSel" class="form-control">';
							foreach ($categories as $cat) { $html .= '<option value="'.$cat.'">'.$cat.'</option>'; }
		$html .= '			</select>
						</div>
						<div class="col-md-4"><label>OR New Category</label><input name="genCatNew" type="text" class="form-control" placeholder="New name..."></div>
					</div>
				  </div>';

		$html .= '<nav><div class="nav nav-tabs" id="nav-tab" role="tablist">';
		foreach ($categories as $index => $cat) {
			$active = ($index === 0) ? 'active' : '';
			$html .= '<a class="nav-item nav-link js-tab-drop '.$active.'" data-cat="'.$cat.'" data-toggle="tab" href="#content-'.$index.'" role="tab">'.$cat.'</a>';
		}
		$html .= '</div></nav>';

		$html .= '<div class="tab-content p-3 border border-top-0 bg-white" id="nav-tabContent">';
		foreach ($categories as $index => $cat) {
			$active = ($index === 0) ? 'show active' : '';
			$html .= '<div class="tab-pane fade '.$active.'" id="content-'.$index.'" role="tabpanel">';
			$html .= '<table class="table table-hover align-middle"><tbody>';
			foreach ($snippets as $id => $item) {
				if ($item['category'] === $cat) {
					$code = '[snippet url="'.$item['url'].'"]';
					$html .= '<tr draggable="true" class="js-draggable" data-id="'.$id.'" id="row-'.$id.'">
								<td style="cursor:grab; width:30px; vertical-align:middle;">☰</td>
								<td class="js-content-view">
									<strong>'.htmlspecialchars($item['title']).'</strong><br><small class="text-muted">'.$item['url'].'</small>
								</td>
								<td class="js-content-edit d-none">
									<input type="text" name="editTitle" class="form-control mb-1" value="'.htmlspecialchars($item['title']).'">
									<input type="text" name="editUrl" class="form-control mb-1" value="'.$item['url'].'">
									<textarea name="editDesc" class="form-control">'.htmlspecialchars($item['desc']).'</textarea>
									<input type="hidden" name="edit_id" value="'.$id.'">
									<button type="submit" name="update_snippet" class="btn btn-success btn-sm mt-1">Update</button>
									<button type="button" class="btn btn-secondary btn-sm mt-1" onclick="cancelEdit('.$id.')">Cancel</button>
								</td>
								<td style="width:280px; vertical-align:middle;">
									<div class="input-group input-group-sm">
										<input type="text" class="form-control" value="'.htmlspecialchars($code).'" id="code-'.$id.'" readonly>
										<div class="input-group-append">
											<button class="btn btn-outline-primary" type="button" onclick="copyToClipboard(\'code-'.$id.'\', this)">Copy</button>
										</div>
									</div>
								</td>
								<td class="text-right" style="vertical-align:middle;">
									<button type="button" class="btn btn-primary btn-sm" onclick="enableEdit('.$id.')">Edit</button>
									<button type="submit" name="delete_snippet" value="'.$id.'" class="btn btn-danger btn-sm">Delete</button>
								</td>
							</tr>';
				}
			}
			$html .= '</tbody></table></div>';
		}
		$html .= '</div>';

		$html .= '<div class="mt-4 p-3 border rounded bg-light">
					<h5>Manage Categories</h5>
					<div class="row mt-2">
						<div class="col-md-6 border-right">
							<label>Rename Category</label>
							<div class="input-group">
								<select name="old_cat_name" class="form-control">';
								foreach($categories as $cat) { $html .= '<option value="'.$cat.'">'.$cat.'</option>'; }
		$html .= '				</select>
								<input type="text" name="new_cat_name" class="form-control" placeholder="New name">
								<div class="input-group-append"><button type="submit" name="rename_cat" class="btn btn-warning">Rename</button></div>
							</div>
						</div>
						<div class="col-md-6 text-right">
							<label class="d-block">Danger Zone</label>
							<button type="submit" name="nuke_all_snippets" class="btn btn-outline-danger btn-sm" onclick="return confirm(\'Delete EVERYTHING?\')">Delete ALL Snippets</button>
						</div>
					</div>
				  </div>';

		$html .= '<script>
		function copyToClipboard(inputId, btn) {
			var copyText = document.getElementById(inputId);
			copyText.select();
			copyText.setSelectionRange(0, 99999);
			document.execCommand("copy");
			
			var originalText = btn.innerHTML;
			btn.innerHTML = "Copied!";
			btn.classList.replace("btn-outline-primary", "btn-success");
			setTimeout(function() {
				btn.innerHTML = originalText;
				btn.classList.replace("btn-success", "btn-outline-primary");
			}, 2000);
		}
		function enableEdit(id) {
			const row = document.getElementById("row-"+id);
			row.querySelector(".js-content-view").classList.add("d-none");
			row.querySelector(".js-content-edit").classList.remove("d-none");
		}
		function cancelEdit(id) {
			const row = document.getElementById("row-"+id);
			row.querySelector(".js-content-view").classList.remove("d-none");
			row.querySelector(".js-content-edit").classList.add("d-none");
		}
		document.querySelectorAll(".js-draggable").forEach(row => {
			row.addEventListener("dragstart", e => { e.dataTransfer.setData("text/plain", row.dataset.id); });
		});
		document.querySelectorAll(".js-tab-drop").forEach(tab => {
			tab.addEventListener("dragover", e => { e.preventDefault(); tab.style.background = "#f0f0f0"; });
			tab.addEventListener("dragleave", e => { tab.style.background = ""; });
			tab.addEventListener("drop", e => {
				e.preventDefault();
				const id = e.dataTransfer.getData("text/plain");
				const newCat = tab.dataset.cat;
				const f = document.getElementById("jsform");
				const i1 = document.createElement("input"); i1.type="hidden"; i1.name="move_id"; i1.value=id;
				const i2 = document.createElement("input"); i2.type="hidden"; i2.name="new_cat"; i2.value=newCat;
				f.appendChild(i1); f.appendChild(i2); f.submit();
			});
		});
		</script>';

		return $html;
	}

	public function siteBodyEnd()
	{
		$snippetsData = json_encode($this->getSnippets());
		return <<<JS
<script>
window.addEventListener('DOMContentLoaded', () => {
    const snippets = $snippetsData;
    const pattern = /\[snippet url="(.*?)"\]/g;
    document.body.innerHTML = document.body.innerHTML.replace(pattern, (match, url) => {
        const data = snippets.find(s => s.url === url) || { title: 'Link', desc: '', url: url };
        const host = data.url.replace('https://','').replace('http://','').split('/')[0];
        return `<div class="snippet-card" onclick="window.open('\${data.url}', '_blank')" style="cursor:pointer; border: 1px solid #e2e8f0; padding: 20px; margin: 20px 0; background: #fff; color: #1a202c; font-family: sans-serif; display: flex; gap: 18px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <img src="https://www.google.com/s2/favicons?sz=128&domain=\${host}" style="width: 44px; height: 44px; border-radius: 4px;">
            <div>
                <strong style="display: block; font-size: 18px; margin-bottom: 4px;">\${data.title}</strong>
                <p style="margin: 0 0 8px 0; color: #4a5568; font-size: 14px;">\${data.desc}</p>
                <span style="color: #a0aec0; font-size: 12px; font-weight: 600; text-transform: uppercase;">\${host}</span>
            </div>
        </div>`;
    });
});
</script>
JS;
	}
}