<?php

class pluginLinks extends Plugin
{
	public function init()
	{
		$jsondb = json_encode(array(
			'ByAldon' => 'https://github.com/ByAldon'

		));

		$this->dbFields = array(
			'label' => 'Links',
			'jsondb' => $jsondb
		);

		$this->formButtons = false;
	}

	public function post()
	{
		$jsondb = $this->db['jsondb'];
		$jsondb = Sanitize::htmlDecode($jsondb);
		$links = json_decode($jsondb, true);

		if (isset($_POST['deleteLink'])) {
			unset($links[$_POST['deleteLink']]);
		} 
		elseif (isset($_POST['addLink'])) {
			$name = $_POST['linkName'];
			$url = $_POST['linkURL'];
			$oldName = $_POST['oldName'];

			if (!empty($name)) {
				if (!empty($oldName) && isset($links[$oldName])) {
					unset($links[$oldName]);
				}
				$links[$name] = $url;
			}
		}
		elseif (isset($_POST['moveUp']) || isset($_POST['moveDown'])) {
			$key = isset($_POST['moveUp']) ? $_POST['moveUp'] : $_POST['moveDown'];
			$direction = isset($_POST['moveUp']) ? -1 : 1;
			
			$keys = array_keys($links);
			$pos = array_search($key, $keys);
			$newPos = $pos + $direction;

			if ($newPos >= 0 && $newPos < count($keys)) {
				$out = array_splice($keys, $pos, 1);
				array_splice($keys, $newPos, 0, $out);
				
				$newLinks = array();
				foreach ($keys as $k) {
					$newLinks[$k] = $links[$k];
				}
				$links = $newLinks;
			}
		}

		$this->db['label'] = Sanitize::html($_POST['label']);
		$this->db['jsondb'] = Sanitize::html(json_encode($links));

		return $this->save();
	}

	public function form()
	{
		global $L;

		$jsondb = $this->getValue('jsondb', false);
		$links = json_decode($jsondb, true);

		$editName = '';
		$editURL = '';
		if (isset($_GET['editLink']) && isset($links[$_GET['editLink']])) {
			$editName = $_GET['editLink'];
			$editURL = $links[$editName];
		}

		$html  = '<div class="alert alert-primary" role="alert">'.$this->description().'</div>';

		// Gebruik standaard 'Label' vertaling uit Bludit core
		$html .= '<div>';
		$html .= '<label>' . $L->get('Label') . '</label>';
		$html .= '<input name="label" class="form-control" type="text" value="' . $this->getValue('label') . '">';
		$html .= '</div>';
		$html .= '<div><button name="save" class="btn btn-primary my-2" type="submit">' . $L->get('Save') . '</button></div>';

		$html .= '<hr>';

		// Vertalingen voor toevoegen en bewerken
		$html .= '<h4 class="mt-3">' . ($editName ? $L->get('Edit') : $L->get('Add')) . '</h4>';
		$html .= '<input type="hidden" name="oldName" value="'.$editName.'">';
		$html .= '<div><label>'.$L->get('Name').'</label><input name="linkName" type="text" class="form-control" value="'.$editName.'"></div>';
		$html .= '<div><label>'.$L->get('URL').'</label><input name="linkURL" type="text" class="form-control" value="'.$editURL.'"></div>';
		$html .= '<div>';
		$html .= '<button name="addLink" class="btn btn-success my-2" type="submit">' . ($editName ? $L->get('Save') : $L->get('Add')) . '</button>';
		if ($editName) {
			$html .= ' <a href="'.HTML_PATH_ADMIN_ROOT.'configure-plugin/'.$this->className().'" class="btn btn-secondary my-2">'.$L->get('Cancel').'</a>';
		}
		$html .= '</div>';

		if (!empty($links)) {
			// 'Links' is ook een standaard term in Bludit
			$html .= '<h4 class="mt-3">'.$L->get('Links').'</h4>';
			foreach ($links as $name => $url) {
				$html .= '<div class="card my-2 p-2" style="background: #f8f9fa;"><div class="row align-items-center">';
				$html .= '<div class="col-7"><strong>'.$name.'</strong><br><small class="text-muted">'.$url.'</small></div>';
				$html .= '<div class="col-5 text-right">';
				$html .= '<button name="moveUp" value="'.$name.'" title="Omhoog" class="btn btn-sm btn-outline-secondary py-0" type="submit">↑</button>';
				$html .= '<button name="moveDown" value="'.$name.'" title="Omlaag" class="btn btn-sm btn-outline-secondary py-0 mr-2" type="submit">↓</button>';
				$html .= '<a href="'.HTML_PATH_ADMIN_ROOT.'configure-plugin/'.$this->className().'?editLink='.urlencode($name).'" class="btn btn-sm btn-info mr-1">'.$L->get('Edit').'</a>';
				$html .= '<button name="deleteLink" value="'.$name.'" class="btn btn-sm btn-danger" type="submit">'.$L->get('Delete').'</button>';
				$html .= '</div></div></div>';
			}
		}

		return $html;
	}

	public function siteSidebar()
	{
		$html  = '<div class="plugin plugin-links">';
		if ($this->getValue('label')) {
			$html .= '<h2 class="plugin-label">' . $this->getValue('label') . '</h2>';
		}
		$html .= '<div class="plugin-content"><ul>';

		$jsondb = $this->getValue('jsondb', false);
		$links = json_decode($jsondb, true);

		if (!empty($links)) {
			foreach ($links as $name => $url) {
				$html .= '<li><a href="' . $url . '">' . $name . '</a></li>';
			}
		}

		$html .= '</ul></div></div>';
		return $html;
	}
}