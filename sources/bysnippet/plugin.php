<?php

class pluginBySnippet extends Plugin {

	public function init()
	{
		$this->dbFields = array('snippets' => '[]');
	}

	private function getSnippets()
	{
		return json_decode($this->getValue('snippets'), true) ?: array();
	}

	public function name() { 
		return 'BySnippet'; 
	}

	public function description() {
		return 'Automatically generates beautiful link-cards from [snippet url="..."] shortcodes with drag-and-drop category management.';
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

		if (!empty($_POST['genUrl'])) {
			$meta = $this->fetchMeta($_POST['genUrl']);
			$snippets[] = array(
				'url'      => $_POST['genUrl'],
				'title'    => $meta['title'],
				'desc'     => $meta['desc'],
				'category' => !empty($_POST['genCat']) ? ucfirst(strtolower($_POST['genCat'])) : 'General'
			);
		}

		if (isset($_POST['move_id']) && isset($_POST['new_cat'])) {
			$id = $_POST['move_id'];
			$snippets[$id]['category'] = $_POST['new_cat'];
		}

		if (isset($_POST['delete_snippet'])) {
			unset($snippets[$_POST['delete_snippet']]);
			$snippets = array_values($snippets);
		}

		$this->db['snippets'] = json_encode($snippets);
		return $this->save();
	}

	public function form()
	{
		$snippets = $this->getSnippets();
		$categories = array_unique(array_column($snippets, 'category'));
		if (empty($categories)) { $categories = ['General']; }

		$html = '<div class="alert alert-info">Drag a row and drop it on a tab to change its category. Click <b>Save</b> to confirm changes.</div>';
		
		$html .= '<div class="mb-4 p-3 border rounded bg-light">
					<h5>Add New Snippet</h5>
					<div class="row">
						<div class="col-md-6"><label>URL</label><input name="genUrl" type="text" class="form-control" placeholder="https://..."></div>
						<div class="col-md-6"><label>Category</label><input name="genCat" type="text" class="form-control" placeholder="e.g. Tools"></div>
					</div>
				  </div>';

		$html .= '<nav><div class="nav nav-tabs" id="nav-tab" role="tablist">';
		foreach ($categories as $index => $cat) {
			$active = ($index === 0) ? 'active' : '';
			$html .= '<a class="nav-item nav-link js-tab-drop '.$active.'" data-cat="'.$cat.'" data-toggle="tab" href="#content-'.$index.'" role="tab">'.$cat.'</a>';
		}
		$html .= '</div></nav>';

		$html .= '<div class="tab-content p-3 border border-top-0" id="nav-tabContent">';
		foreach ($categories as $index => $cat) {
			$active = ($index === 0) ? 'show active' : '';
			$html .= '<div class="tab-pane fade '.$active.'" id="content-'.$index.'" role="tabpanel">';
			$html .= '<table class="table table-hover"><thead><tr><th>Drag</th><th>Preview</th><th>Shortcode</th><th>Action</th></tr></thead><tbody>';
			
			foreach ($snippets as $id => $item) {
				if ($item['category'] === $cat) {
					$html .= '<tr draggable="true" class="js-draggable" data-id="'.$id.'">';
					$html .= '<td style="cursor:grab; font-size:20px;">☰</td>';
					$html .= '<td><strong>'.htmlspecialchars($item['title']).'</strong><br><small>'.$item['url'].'</small></td>';
					$html .= '<td><input type="text" class="form-control form-control-sm" value=\'[snippet url="'.$item['url'].'"]\' readonly onclick="this.select();"></td>';
					$html .= '<td><button type="submit" name="delete_snippet" value="'.$id.'" class="btn btn-danger btn-sm">Delete</button></td>';
					$html .= '</tr>';
				}
			}
			$html .= '</tbody></table></div>';
		}
		$html .= '</div>';

		$html .= '<script>
		document.querySelectorAll(".js-draggable").forEach(row => {
			row.addEventListener("dragstart", e => { e.dataTransfer.setData("text/plain", row.dataset.id); });
		});
		document.querySelectorAll(".js-tab-drop").forEach(tab => {
			tab.addEventListener("dragover", e => { e.preventDefault(); tab.style.background = "#e9ecef"; });
			tab.addEventListener("dragleave", e => { tab.style.background = ""; });
			tab.addEventListener("drop", e => {
				e.preventDefault();
				const id = e.dataTransfer.getData("text/plain");
				const newCat = tab.dataset.cat;
				const inputId = document.createElement("input");
				inputId.type = "hidden"; inputId.name = "move_id"; inputId.value = id;
				const inputCat = document.createElement("input");
				inputCat.type = "hidden"; inputCat.name = "new_cat"; inputCat.value = newCat;
				const form = document.getElementById("jsform");
				form.appendChild(inputId); form.appendChild(inputCat);
				form.submit();
			});
		});
		</script>';

		return $html;
	}

	public function siteBodyEnd()
	{
		$snippetsData = $this->getValue('snippets');
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