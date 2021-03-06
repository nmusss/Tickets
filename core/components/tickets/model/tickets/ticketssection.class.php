<?php
/**
 * The TicketsSection CRC for Tickets.
 *
 * @package tickets
 */

require_once MODX_CORE_PATH.'components/tickets/processors/mgr/section/create.class.php';
require_once MODX_CORE_PATH.'components/tickets/processors/mgr/section/update.class.php';

class TicketsSection extends modResource {
	public $showInContextMenu = true;
	public $allowChildrenResources = false;

	function __construct(xPDO & $xpdo) {
		parent :: __construct($xpdo);

		$this->set('class_key','TicketsSection');
		$this->set('comments',0);
		$this->set('views',0);
		$this->set('votes',0);
		$this->set('tickets',0);
	}


	/**
	 * {@inheritDoc}
	 * @return object|null
	 */
	public static function load(xPDO & $xpdo, $className, $criteria= null, $cacheFlag= true){
		if (!is_object($criteria)) {
			$criteria= $xpdo->getCriteria($className, $criteria, $cacheFlag);
		}
		$xpdo->addDerivativeCriteria($className, $criteria);
		return parent::load($xpdo, $className, $criteria, $cacheFlag);
	}


	/**
	 * {@inheritDoc}
	 * @return array
	 */
	public static function loadCollection(xPDO & $xpdo, $className, $criteria= null, $cacheFlag= true){
		if (!is_object($criteria)) {
			$criteria= $xpdo->getCriteria($className, $criteria, $cacheFlag);
		}
		$xpdo->addDerivativeCriteria($className, $criteria);
		return parent::loadCollection($xpdo, $className, $criteria, $cacheFlag);
	}


	/** {@inheritDoc} */
	public static function getControllerPath(xPDO &$modx) {
		return $modx->getOption('tickets.core_path',null,$modx->getOption('core_path').'components/tickets/').'controllers/section/';
	}


	/**
	 * {@inheritDoc}
	 */
	public function getContextMenuText() {
		$this->xpdo->lexicon->load('tickets:default');
		return array(
			'text_create' => $this->xpdo->lexicon('tickets_section'),
			'text_create_here' => $this->xpdo->lexicon('tickets_section_create_here'),
		);
	}


	/**
	 * {@inheritDoc}
	 */
	public function getResourceTypeName() {
		$this->xpdo->lexicon->load('tickets:default');
		return $this->xpdo->lexicon('tickets_section');
	}


	/**
	 * {@inheritDoc}
	 */
	public function get($k, $format = null, $formatTemplate= null) {
		$fields = array('comments','views','votes','tickets');

		if (is_array($k)) {
			$k = array_merge($k, $fields);
			$value = parent::get($k, $format, $formatTemplate);
		}
		else {
			switch ($k) {
				case 'comments': $value = $this->getCommentsCount(); break;
				case 'views': $value = $this->getViewsCount(); break;
				case 'votes': $value = $this->getVotesSum(); break;
				case 'tickets': $value = $this->getTicketsCount(); break;
				default: $value = parent::get($k, $format, $formatTemplate);
			}
		}

		return $value;
	}


	/**
	 * {@inheritDoc}
	 */
	public function toArray($keyPrefix= '', $rawValues= false, $excludeLazy= false, $includeRelated= false) {
		$array = array_merge(parent::toArray(), $this->getVirtualFields());

		return $array;
	}


	/**
	 * {@inheritDoc}
	 */
	public function process() {
		//$this->xpdo->setPlaceholders($this->getVirtualFields());
		return parent::process();
	}


	/**
	 * {@inheritDoc}
	 */
	public function getContent(array $options = array()) {
		$content = parent::getContent($options);

		return $content;
	}


	/**
	 * Clearing cache of this resource
	 * @param string $context Key of context for clearing
	 * @return void
	 */
	public function clearCache($context = null) {
		if (empty($context)) {
			$context = $this->context_key;
		}
		$this->_contextKey = $context;

		/** @var xPDOFileCache $cache */
		$cache = $this->xpdo->cacheManager->getCacheProvider($this->xpdo->getOption('cache_resource_key', null, 'resource'));
		$key = $this->getCacheKey();
		$cache->delete($key, array('deleteTop' => true));
		$cache->delete($key);
	}


	/**
	 * Shorthand for getting virtual Ticket fields
	 *
	 * @return array $array Array with virtual fields
	 */
	function getVirtualFields() {
		$array = array(
			'comments' => $this->getCommentsCount()
			,'views' => $this->getViewsCount()
			,'tickets' => $this->getTicketsCount()
		);

		return $array;
	}


	/**
	 * Returns count of views of Tickets by users in this Section
	 *
	 * @return integer $count Total count of views
	 */
	public function getViewsCount() {
		$q = $this->xpdo->newQuery('Ticket', array('parent' => $this->id, 'published' => 1, 'deleted' => 0));
		$q->leftJoin('TicketView','TicketView', "`TicketView`.`parent` = `Ticket`.`id`");
		$q->select('COUNT(`TicketView`.`parent`) as `views`');

		$count = 0;
		if ($q->prepare() && $q->stmt->execute()) {
			$count = (integer) $q->stmt->fetch(PDO::FETCH_COLUMN);
		}
		return $count;
	}


	/**
	 * Returns count of comments to Tickets in this Section
	 *
	 * @return integer $count Total count of comment
	 */
	public function getCommentsCount() {
		$q = $this->xpdo->newQuery('Ticket', array('parent' => $this->id, 'published' => 1, 'deleted' => 0));
		$q->leftJoin('TicketThread','TicketThread', "`TicketThread`.`resource` = `Ticket`.`id`");
		$q->leftJoin('TicketComment','TicketComment', "`TicketThread`.`id` = `TicketComment`.`thread`");
		$q->select('COUNT(`TicketComment`.`id`) as `comments`');

		$count = 0;
		if ($q->prepare() && $q->stmt->execute()) {
			$count = (integer) $q->stmt->fetch(PDO::FETCH_COLUMN);
		}
		return $count;
	}


	/**
	 * Returns sum of votes to Tickets by users in this Section
	 *
	 * @return integer $count Total sum of votes
	 */
	public function getVotesSum() {
		$q = $this->xpdo->newQuery('Ticket', array('parent' => $this->id, 'published' => 1, 'deleted' => 0));
		$q->leftJoin('TicketVote','TicketVote', "`TicketVote`.`id` = `Ticket`.`id` AND `TicketVote`.`class` = 'Ticket'");
		$q->select('SUM(`TicketVote`.`value`) as `votes`');

		$sum = 0;
		if ($q->prepare() && $q->stmt->execute()) {
			$sum = (integer) $q->stmt->fetch(PDO::FETCH_COLUMN);
		}
		return $sum;
	}


	/**
	 * Returns count of tickets in this Section
	 *
	 * @return integer $count Total sum of votes
	 */
	public function getTicketsCount() {
		return $this->xpdo->getCount('Ticket', array('parent' => $this->id, 'published' => 1, 'deleted' => 0));
	}


	/**
	 * @param array $node
	 * @return array
	 */
	public function prepareTreeNode(array $node = array()) {
		$this->xpdo->lexicon->load('tickets:default');
		$menu = array();

		$idNote = $this->xpdo->hasPermission('tree_show_resource_ids') ? ' <span dir="ltr">('.$this->id.')</span>' : '';
		$menu[] = array(
			'text' => '<b>'.$this->get('pagetitle').'</b>'.$idNote,
			'handler' => 'Ext.emptyFn',
		);
		$menu[] = '-';
		$menu[] = array(
			'text' => $this->xpdo->lexicon('tickets_section_management'),
			'handler' => 'this.editResource',
		);
		/*
		$menu[] = array(
			'text' => $this->xpdo->lexicon('create')
			,'handler' => 'Ext.emptyFn'
			,'menu' => array('items' => array(
				array(
					'text' => $this->xpdo->lexicon('ticket')
					,'handler' => 'function(itm,e) { var tree = Ext.getCmp("modx-resource-tree"); itm.classKey = "Ticket"; tree.createResourceHere(itm,e); }'
				)
			))
		);
		*/
		$menu[] = array(
			'text' => $this->xpdo->lexicon('ticket_create_here')
			,'handler' => 'function(itm,e) { var tree = Ext.getCmp("modx-resource-tree"); itm.classKey = "Ticket"; tree.createResourceHere(itm,e); }'
		);

		$menu[] = '-';
		$menu[] = array(
			'text' => $this->xpdo->lexicon('tickets_section_duplicate'),
			'handler' => 'function(itm,e) {itm.classKey = "TicketsSection"; this.duplicateResource(itm,e); }',
		);

		if ($this->get('published')) {
			$menu[] = array(
				'text' => $this->xpdo->lexicon('tickets_section_unpublish'),
				'handler' => 'this.unpublishDocument',
			);
		} else {
			$menu[] = array(
				'text' => $this->xpdo->lexicon('tickets_section_publish'),
				'handler' => 'this.publishDocument',
			);
		}
		if ($this->get('deleted')) {
			$menu[] = array(
				'text' => $this->xpdo->lexicon('tickets_section_undelete'),
				'handler' => 'this.undeleteDocument',
			);
		} else {
			$menu[] = array(
				'text' => $this->xpdo->lexicon('tickets_section_delete'),
				'handler' => 'this.deleteDocument',
			);

		}
		$menu[] = '-';
		$menu[] = array(
			'text' => $this->xpdo->lexicon('tickets_section_view'),
			'handler' => 'this.preview',
		);

		$node['menu'] = array('items' => $menu);
		$node['hasChildren'] = true;
		return $node;
	}
}