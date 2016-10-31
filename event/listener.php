<?php
/**
 *
 * You Me We Us Filter. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2016, Mike Kros, http://www.mickroz.nl
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace mickroz\ymwus\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * You Me We Us Filter Event listener.
 */
class listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.page_footer'	=> 'rewrite_assets',
		);
	}

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\auth\auth */
	protected $auth;
	
	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/**
	 * Constructor
	 *
	 * @param \phpbb\controller\helper	$helper		Controller helper object
	 * @param \phpbb\template\template	$template	Template object
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\user $user, \phpbb\auth\auth $auth, \phpbb\controller\helper $helper, \phpbb\template\template $template)
	{
		$this->config = $config;
		$this->db = $db;
		$this->user = $user;
		$this->auth = $auth;
		$this->helper = $helper;
		$this->template = $template;
	}

	/**
	 * Rewrites an image tag into a version that can be used by a Camo asset server
	 *
	 * @param	array	$object	The array containing the data to rewrite
	 * @param	string	$key	The key into the array. The element to rewrite.
	 * @return	void
	 */
	private function rewrite_text(&$object, $key)
	{
		//Grab founder
		$sql = 'SELECT user_id, username, user_colour
			FROM ' . USERS_TABLE . '
			WHERE user_type = ' . USER_FOUNDER;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
	
		$founder_name = get_username_string('username', $row['user_id'], $row['username'], $row['user_colour'], $this->user->lang['GUEST']);
		$founder_full = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour'], $this->user->lang['GUEST']);
		
		if (!empty($object[$key]))
		{
			if ($key == 'POST_SUBJECT' || $key == 'SUBJECT')
			{
				$messagerow['POST_SUBJECT'] = $this->filter_username($your_username, $my_username, $messagerow['SUBJECT']);
				$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
				$my_username = $object['POST_AUTHOR'];
				if ($key == 'SUBJECT')
				{
					$my_username = $object['MESSAGE_AUTHOR'];
				}
				$object['POST_SUBJECT'] = $this->filter_username($your_username, $my_username, $object['POST_SUBJECT']);
				if ($key == 'SUBJECT')
				{
					$object['SUBJECT'] = $this->filter_username($your_username, $my_username, $object['SUBJECT']);
				}
			}
			
			if ($key == 'MESSAGE' || $key == 'PREVIEW_MESSAGE')
			{
				$my_username = $object['POST_AUTHOR_FULL'];
				if ($key == 'PREVIEW_MESSAGE')
				{
					$my_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
				}
				
				// is this a quoted text?
				$filter_start = "<blockquote>";
				$filter_end = "</blockquote>";
				$filter_output = $this->get_between($filtered_message, $filter_start, $filter_end);
				$find_me = strpos($filter_output, '[me]');
				if ($find_me !== false)
				{
					$find_quoter = strpos($filter_output, '<cite>');
					if ($find_quoter !== false)
					{
						// we have found [me] in a quoted text, replace it with quoted user
						$quoted_name = $this->get_between($filter_output, "<cite>", "</cite>");
						$quoted_cleaned = str_replace(' ' . $this->user->lang['WROTE'] . ':', '', $quoted_name);
						$my_username = $quoted_cleaned;
					}
					else
					{
						$my_username = $my_username;
					}
				}
				else
				{
					$my_username = $my_username;
				}

				$your_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
				if ($key == 'MESSAGE')
				{
					$object['MESSAGE'] = $this->filter_username($your_username, $my_username, $object['MESSAGE']);
				}
				$object['PREVIEW_MESSAGE'] = $this->filter_username($your_username, $my_username, $object['PREVIEW_MESSAGE']);
			}
			
			if ($key == 'SIGNATURE')
			{
				$your_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
				$my_username = $object['POST_AUTHOR_FULL'];
				$object['SIGNATURE'] = $this->filter_username($your_username, $my_username, $object['SIGNATURE']);
			}
			if ($key == 'SIGNATURE_PREVIEW')
			{
				$your_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
				$my_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
				$object['SIGNATURE_PREVIEW'] = $this->filter_username($your_username, $my_username, $object['SIGNATURE_PREVIEW']);
			}
			
			if ($key == 'FORUM_NAME' || $key == 'FORUM_TITLE')
			{
				$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
				$my_username = $founder_name;
				$object['FORUM_NAME'] = $this->filter_username($your_username, $my_username, $object['FORUM_NAME']);
				$object['FORUM_TITLE'] = $this->filter_username($your_username, $my_username, $object['FORUM_TITLE']);
			}
			
			if ($key == 'TOPIC_TITLE' || $key == 'LAST_POST_SUBJECT' || $key == 'LAST_POST_SUBJECT_TRUNCATED')
			{
				$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
				$my_username = $object['TOPIC_AUTHOR'];
				$object['TOPIC_TITLE'] = $this->filter_username($your_username, $my_username, $object['TOPIC_TITLE']);
				$object['LAST_POST_SUBJECT'] = $this->filter_username($your_username, $my_username, $object['LAST_POST_SUBJECT']);
				$object['LAST_POST_SUBJECT_TRUNCATED'] = $this->filter_username($your_username, $my_username, $object['LAST_POST_SUBJECT_TRUNCATED']);
			}
		}
	}
	
	public function filter_username($your_name, $my_name, $filtered_message)
	{
		$find = array('[we]', '[me]', '[you]', '[us]');
		$replace = array(
			sprintf($this->user->lang['FILTER_WE'], $your_name, $my_name),
			$my_name,
			$your_name,
			'<span style="color: red;">' . $this->config['sitename'] . '</span>',
		);
		$filtered_message = str_replace($find, $replace, $filtered_message);
	
		return $filtered_message;
	}
	
	public function get_between($filter_content, $filter_start, $filter_end)
	{
		$r = explode($filter_start, $filter_content);
		if (isset($r[1]))
		{
			$r = explode($filter_end, $r[1]);
			return $r[0];
		}
		return '';
	}
	
	/**
	 * A sample PHP event
	 * Modifies the names of the forums on index
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function rewrite_assets($event)
	{
		
		$this->user->add_lang_ext('mickroz/ymwus', 'common');
		
		global $request;
		global $phpbb_container;
		$context = $phpbb_container->get('template_context');
		$rootref = &$context->get_root_ref();
		$tpldata = &$context->get_data_ref();
		
		if (isset($tpldata['forumrow']))
		{
			foreach ($tpldata['forumrow'] as $index => &$forumrow)
			{
				$this->rewrite_text($forumrow, 'FORUM_DESC');
				$this->rewrite_text($forumrow, 'FORUM_NAME');
				$this->rewrite_text($forumrow, 'SUBFORUMS');
				$this->rewrite_text($forumrow, 'LAST_POST_SUBJECT');
				$this->rewrite_text($forumrow, 'LAST_POST_SUBJECT_TRUNCATED');
			}
		}
		
		if (isset($tpldata['topicrow']))
		{
			foreach ($tpldata['topicrow'] as $index => &$topicrow)
			{
				$this->rewrite_text($topicrow, 'TOPIC_TITLE');
				$this->rewrite_text($topicrow, 'LAST_POST_SUBJECT');
			}
		}
		
		// Viewtopic
		if (isset($tpldata['postrow']))
		{
			foreach ($tpldata['postrow'] as $index => &$postrow)
			{
				$this->rewrite_text($postrow, 'POST_SUBJECT');
				$this->rewrite_text($postrow, 'MESSAGE');
				$this->rewrite_text($postrow, 'SIGNATURE');
			}
		}

		if (isset($tpldata['jumpbox_forums']))
		{
			foreach ($tpldata['jumpbox_forums'] as $index => &$jumpbox_forums)
			{
				$this->rewrite_text($jumpbox_forums, 'FORUM_NAME');
			}
		}
		
		if (isset($tpldata['.'][0]['FORUM_NAME']))
		{
			$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
			$my_username = $tpldata['.'][0]['TOPIC_AUTHOR'];
			$tpldata['.'][0]['FORUM_NAME'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['FORUM_NAME']);
		}
		
		if (isset($tpldata['.'][0]['TOPIC_TITLE']))
		{
			$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
			$my_username = $tpldata['.'][0]['TOPIC_AUTHOR'];
			$tpldata['.'][0]['TOPIC_TITLE'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['TOPIC_TITLE']);
		}
		
		if (isset($tpldata['navlinks']))
		{
			foreach ($tpldata['navlinks'] as $index => &$navlinks)
			{
				$this->rewrite_text($navlinks, 'FORUM_NAME');
			}
		}

		// UCP
		$prefix = '';

		//Test to see if we're in preview mode
		if (isset($tpldata['.'][0]['PREVIEW_MESSAGE']))
		{
			$prefix = 'PREVIEW_';
		}
		
		//UCP - PM - View - Author subject
		if (isset($tpldata['.'][0][$prefix . 'SUBJECT']))
		{
			$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
			$my_username = $tpldata['.'][0]['MESSAGE_AUTHOR'];
			if($prefix)
			{
				$my_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
			}
			$tpldata['.'][0][$prefix . 'SUBJECT'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0][$prefix . 'SUBJECT']);
		}
		
		//UCP - PM - View - Author message
		if (isset($tpldata['.'][0][$prefix . 'MESSAGE']))
		{
			$your_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
			$my_username = $tpldata['.'][0]['MESSAGE_AUTHOR_FULL'];
			if($prefix)
			{
				$my_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
			}
			$tpldata['.'][0][$prefix . 'MESSAGE'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0][$prefix . 'MESSAGE']);
		}
		
		//UCP - PM - Compose - Signature
		if (isset($tpldata['.'][0][$prefix . 'SIGNATURE']))
		{
			$your_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
			$my_username = $tpldata['.'][0]['MESSAGE_AUTHOR_FULL'];
			if($prefix)
			{
				$my_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
			}
			$tpldata['.'][0][$prefix . 'SIGNATURE'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0][$prefix . 'SIGNATURE']);
		}
		
		//UCP - PM - Inbox/Outbox
		if (isset($tpldata['messagerow']))
		{
			foreach ($tpldata['messagerow'] as $index => &$messagerow)
			{
				$this->rewrite_text($messagerow, 'SUBJECT');
			}
		}
		
		$this->rewrite_text($rootref, 'SIGNATURE_PREVIEW');	//UCP - Profile - Signature
		
		if ($request->variable('mode', '') != 'signature')
		{
			$this->rewrite_text($rootref, 'SIGNATURE');			//UCP - PM - View - Author signature, Memberlist - Profile - Signature
		}
		$this->rewrite_text($rootref, 'POST_PREVIEW');		//MCP - Reported post

		//UCP - PM - Message history, MCP - Reported Post - Topic Review
		if (isset($tpldata['topic_review_row']))
		{
			foreach ($tpldata['topic_review_row'] as $index => &$topic_review_row)
			{
				$this->rewrite_text($topic_review_row, 'MESSAGE');
				$this->rewrite_text($topic_review_row, 'POST_SUBJECT');
			}
		}
		//UCP - PM - Message history (Sent messages)
		if (isset($tpldata['history_row']))
		{
			foreach ($tpldata['history_row'] as $index => &$history_row)
			{
				$this->rewrite_text($history_row, 'MESSAGE');
				$this->rewrite_text($history_row, 'SUBJECT');
			}
		}
		//Search results
		if (isset($tpldata['searchresults']))
		{
			foreach ($tpldata['searchresults'] as $index => &$search_results)
			{
				$this->rewrite_text($search_results, 'MESSAGE');
				$this->rewrite_text($search_results, 'FORUM_TITLE');
				$this->rewrite_text($search_results, 'TOPIC_TITLE');
				$this->rewrite_text($search_results, 'POST_SUBJECT');
			}
		}	
	}
}
