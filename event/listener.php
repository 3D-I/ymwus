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
			'core.page_footer'	=> 'rewrite_text',
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
	public function rewrite_text($event)
	{
		
		global $request;
		global $phpbb_container;
		$context = $phpbb_container->get('template_context');
		$rootref = &$context->get_root_ref();
		$tpldata = &$context->get_data_ref();
		
		$page_name = substr($this->user->page['page_name'], 0, strpos($this->user->page['page_name'], '.'));

		//Grab founder
		$sql = 'SELECT user_id, username, user_colour
			FROM ' . USERS_TABLE . '
			WHERE user_type = ' . USER_FOUNDER;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
	
		$founder_name = get_username_string('username', $row['user_id'], $row['username'], $row['user_colour'], $this->user->lang['GUEST']);
		$founder_full = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour'], $this->user->lang['GUEST']);
		
		//Now we need to handle some pages
		switch ($page_name)
		{
			case 'memberlist':
				//Viewing user profile
				if ($request->variable('mode', '') == 'viewprofile')
				{					
					if (isset($tpldata['.'][0]['SIGNATURE']))
					{
						$your_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $this->user->lang['GUEST']);
						$my_username = $tpldata['.'][0]['USERNAME_FULL'];
						$tpldata['.'][0]['SIGNATURE'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['SIGNATURE']);
					}
				}
			break;
			
			case 'posting':

				if (!empty($tpldata['navlinks']))
				{
					foreach ($tpldata['navlinks'] as $index => &$navlinks)
					{
						$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);

						$tpldata['navlinks'][$row]['FORUM_NAME'] = $this->filter_username($your_username, $my_username, $navlinks['FORUM_NAME']);
					}
				}

				if (isset($tpldata['.'][0]['TOPIC_TITLE']))
				{
					$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					if(!empty($tpldata['topic_review_row']))
					{
						$my_username = $tpldata['topic_review_row'][0]['POST_AUTHOR'];
					}
					
					$tpldata['.'][0]['TOPIC_TITLE'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['TOPIC_TITLE']);
				}
				
				//Topic review area shown when posting a reply
				if (!empty($tpldata['topic_review_row']))
				{
					$your_userfull = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					
					foreach ($tpldata['topic_review_row'] as $row => $topic_review_row)
					{	
						$my_userfull = $tpldata['topic_review_row'][0]['POST_AUTHOR_FULL'];
						$my_username = $tpldata['topic_review_row'][0]['POST_AUTHOR'];
						
						$tpldata['topic_review_row'][$row]['MESSAGE'] = $this->filter_username($your_userfull, $my_userfull, $topic_review_row['MESSAGE']);
						$tpldata['topic_review_row'][$row]['POST_SUBJECT'] = $this->filter_username($your_username, $my_username, $topic_review_row['POST_SUBJECT']);
					}
				}

				//Message preview
				if (isset($tpldata['.'][0]['PREVIEW_MESSAGE']))
				{
					$your_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$my_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						
					$tpldata['.'][0]['PREVIEW_MESSAGE'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['PREVIEW_MESSAGE']);
				}

				//Signature in post preview
				if (isset($tpldata['.'][0]['PREVIEW_SIGNATURE']))
				{
					$your_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$my_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					
					$tpldata['.'][0]['PREVIEW_SIGNATURE'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['PREVIEW_SIGNATURE']);
				}

			break;

			case 'ucp':
				//UCP - Profile - Signature
				if ($request->variable('mode', '') == 'signature')
				{
					if (isset($tpldata['.'][0]['SIGNATURE_PREVIEW']))
					{
						$your_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					
						$tpldata['.'][0]['SIGNATURE_PREVIEW'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['SIGNATURE_PREVIEW']);
					}
				}

				$prefix = '';

				//Test to see if we're in preview mode
				if (isset($tpldata['.'][0]['PREVIEW_MESSAGE']) || $request->variable('d', '') == true)
				{
					$prefix = 'PREVIEW_';
				}

				//UCP - PM - View - Author subject
				if (isset($tpldata['.'][0][$prefix . 'SUBJECT']))
				{
					$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$my_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					
					$tpldata['.'][0][$prefix . 'SUBJECT'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0][$prefix . 'SUBJECT']);
				}

				//UCP - PM - View - Author message
				if (isset($tpldata['.'][0][$prefix . 'MESSAGE']))
				{
					$your_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$my_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					
					$tpldata['.'][0][$prefix . 'MESSAGE'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0][$prefix . 'MESSAGE']);
				}

				//UCP - PM - Compose - Signature
				if (isset($tpldata['.'][0][$prefix . 'SIGNATURE']))
				{
					if ($request->variable('mode', '') != 'signature')
					{
						$your_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					
						$tpldata['.'][0][$prefix . 'SIGNATURE'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0][$prefix . 'SIGNATURE']);
					}
				}

				//UCP - PM - Inbox/Outbox
				if (!empty($tpldata['messagerow']))
				{
					$your_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					
					foreach ($tpldata['messagerow'] as $row => $messagerow)
					{
						$my_username = $tpldata['messagerow'][0]['MESSAGE_AUTHOR_FULL'];
						
						$tpldata['messagerow'][$row]['MESSAGE'] = $this->filter_username($my_username, $my_username, $messagerow['MESSAGE']);
					}
				}
				
				//UCP - PM - Message history, MCP - Reported Post - Topic Review
				if (!empty($tpldata['topic_review_row']))
				{
					$your_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					
					foreach ($tpldata['topic_review_row'] as $row => $topic_review_row)
					{
						$my_username = $tpldata['topic_review_row'][0]['MESSAGE_AUTHOR_FULL'];
						
						$tpldata['topic_review_row'][$row]['MESSAGE'] = $this->filter_username($my_username, $my_username, $topic_review_row['MESSAGE']);
					}
				}
				
				//UCP - PM - Message history (Sent messages)
				if (!empty($tpldata['history_row']))
				{
					$your_username = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					
					foreach ($tpldata['history_row'] as $row => $history_row)
					{
						$my_username = $tpldata['history_row'][0]['MESSAGE_AUTHOR_FULL'];
						
						$tpldata['history_row'][$row]['MESSAGE'] = $this->filter_username($my_username, $my_username, $history_row['MESSAGE']);
					}
				}

			break;

			case 'mcp':
				if (!empty($tpldata['jumpbox_forums']))
				{			
					foreach ($tpldata['jumpbox_forums'] as $row => $jumpbox_forums)
					{
						$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = $founder_name;

						$tpldata['jumpbox_forums'][$row]['FORUM_NAME'] = $this->filter_username($your_username, $my_username, $jumpbox_forums['FORUM_NAME']);
					}
				}
				
				if (!empty($tpldata['report']))
				{
					foreach ($tpldata['report'] as $row => $report)
					{
						$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = $report['AUTHOR'];


						$tpldata['report'][$row]['FORUM_NAME'] = $this->filter_username($your_username, $founder_name, $report['FORUM_NAME']);
						$tpldata['report'][$row]['TOPIC_TITLE'] = $this->filter_username($your_username, $my_username, $report['TOPIC_TITLE']);
					}
				}
				
				if (!empty($tpldata['topicrow']))
				{
					foreach ($tpldata['topicrow'] as $row => $topicrow)
					{
						$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = $topicrow['TOPIC_AUTHOR'];

						$tpldata['topicrow'][$row]['TOPIC_TITLE'] = $this->filter_username($your_username, $my_username, $topicrow['TOPIC_TITLE']);
						$tpldata['topicrow'][$row]['LAST_POST_SUBJECT'] = $this->filter_username($your_username, $my_username, $topicrow['LAST_POST_SUBJECT']);
					}
				}
				
				if (isset($tpldata['.'][0]['S_FORUM_SELECT']))
				{
					$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$my_username = $tpldata['.'][0]['TOPIC_AUTHOR'];
						
					$tpldata['.'][0]['S_FORUM_SELECT'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['S_FORUM_SELECT']);
				}
				
				//MCP - Reported post
				if (isset($tpldata['.'][0]['L_ONLY_TOPIC']))
				{
					$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$my_username = $tpldata['.'][0]['TOPIC_AUTHOR'];
						
					$tpldata['.'][0]['L_ONLY_TOPIC'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['L_ONLY_TOPIC']);
				}
				
				if (isset($tpldata['.'][0]['S_FORUM_OPTIONS']))
				{
					$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$my_username = $tpldata['.'][0]['TOPIC_AUTHOR'];
						
					$tpldata['.'][0]['S_FORUM_OPTIONS'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['S_FORUM_OPTIONS']);
				}
				
				if (isset($tpldata['.'][0]['TOPIC_TITLE']))
				{
					$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$my_username = $tpldata['.'][0]['TOPIC_AUTHOR'];
						
					$tpldata['.'][0]['TOPIC_TITLE'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['TOPIC_TITLE']);
				}
				
				if (isset($tpldata['.'][0]['FORUM_NAME']))
				{
					$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$my_username = $founder_name;
						
					$tpldata['.'][0]['FORUM_NAME'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['FORUM_NAME']);
					$tpldata['.'][0]['FORUM_DESCRIPTION'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['FORUM_DESCRIPTION']);
				}
				
				if (!empty($tpldata['topic_review_row']))
				{
					$your_userfull = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					
					foreach ($tpldata['topic_review_row'] as $row => $topic_review_row)
					{	
						$my_userfull = $tpldata['topic_review_row'][0]['POST_AUTHOR_FULL'];
						$my_username = $tpldata['topic_review_row'][0]['POST_AUTHOR'];
						
						$tpldata['topic_review_row'][$row]['MESSAGE'] = $this->filter_username($your_userfull, $my_userfull, $topic_review_row['MESSAGE']);
						$tpldata['topic_review_row'][$row]['POST_SUBJECT'] = $this->filter_username($your_username, $my_username, $topic_review_row['POST_SUBJECT']);
					}
				}
				
				if (!empty($tpldata['postrow']))
				{
					foreach ($tpldata['postrow'] as $row => $postrow)
					{
						$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = $postrow['POST_AUTHOR'];

						$tpldata['postrow'][$row]['FORUM_NAME'] = $this->filter_username($your_username, $my_username, $postrow['FORUM_NAME']);
						$tpldata['postrow'][$row]['TOPIC_TITLE'] = $this->filter_username($your_username, $my_username, $postrow['TOPIC_TITLE']);
					}
				}
				
				if (!empty($tpldata['postrow']))
				{
					
					foreach ($tpldata['postrow'] as $row => $postrow)
					{
						$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = $postrow['POST_AUTHOR'];

						$your_userfull = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_userfull = $postrow['POST_AUTHOR_FULL'];


						$tpldata['postrow'][$row]['MESSAGE'] = $this->filter_username($your_userfull, $my_userfull, $postrow['MESSAGE']);
						$tpldata['postrow'][$row]['SIGNATURE'] = $this->filter_username($your_userfull, $my_userfull, $postrow['SIGNATURE']);
						$tpldata['postrow'][$row]['POST_SUBJECT'] = $this->filter_username($your_username, $my_username, $postrow['POST_SUBJECT']);
					}
				}
				
			break;
			
			case 'viewtopic':
			
				if (!empty($tpldata['navlinks']))
				{
					foreach ($tpldata['navlinks'] as $row => $navlinks)
					{
						$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = $founder_name;

						$tpldata['navlinks'][$row]['FORUM_NAME'] = $this->filter_username($your_username, $my_username, $navlinks['FORUM_NAME']);
					}
				}
				
				if (!empty($tpldata['postrow']))
				{
					
					foreach ($tpldata['postrow'] as $row => $postrow)
					{

						$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = $postrow['POST_AUTHOR'];

						$your_userfull = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_userfull = $postrow['POST_AUTHOR_FULL'];

						$tpldata['postrow'][$row]['MESSAGE'] = $this->filter_username($your_userfull, $my_userfull, $postrow['MESSAGE']);
						$tpldata['postrow'][$row]['SIGNATURE'] = $this->filter_username($your_userfull, $my_userfull, $postrow['SIGNATURE']);
						$tpldata['postrow'][$row]['POST_SUBJECT'] = $this->filter_username($your_username, $my_username, $postrow['POST_SUBJECT']);
					}
				}

				if (isset($tpldata['.'][0]['TOPIC_TITLE']))
				{
					$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$my_username = $tpldata['.'][0]['TOPIC_AUTHOR'];
						
					$tpldata['.'][0]['TOPIC_TITLE'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['TOPIC_TITLE']);
				}

				if (isset($tpldata['.'][0]['FORUM_NAME']))
				{
					$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$my_username = $founder_name;
						
					$tpldata['.'][0]['FORUM_NAME'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['FORUM_NAME']);
				}
				
				if (isset($tpldata['.'][0]['L_RETURN_TO_FORUM']))
				{
					$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$my_username = $founder_name;
						
					$tpldata['.'][0]['L_RETURN_TO_FORUM'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['L_RETURN_TO_FORUM']);
				}
				
				if (!empty($tpldata['jumpbox_forums']))
				{			
					foreach ($tpldata['jumpbox_forums'] as $row => $jumpbox_forums)
					{
						$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = $founder_name;

						$tpldata['jumpbox_forums'][$row]['FORUM_NAME'] = $this->filter_username($your_username, $my_username, $jumpbox_forums['FORUM_NAME']);
					}
				}
				
			break;
			
			case 'search':
			
				if (isset($tpldata['.'][0]['S_FORUM_OPTIONS']))
				{
					$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$my_username = $founder_name;
						
					$tpldata['.'][0]['S_FORUM_OPTIONS'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['S_FORUM_OPTIONS']);
				}
				
				//Search results
				if (isset($tpldata['searchresults']))
				{
					foreach ($tpldata['searchresults'] as $row => $searchresults)
					{
						$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = $searchresults['POST_AUTHOR'];

						$your_userfull = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_userfull = $searchresults['POST_AUTHOR_FULL'];

						$tpldata['searchresults'][$row]['MESSAGE'] = $this->filter_username($your_userfull, $my_userfull, $searchresults['MESSAGE']);
						$tpldata['searchresults'][$row]['FORUM_TITLE'] = $this->filter_username($your_username, $founder_name, $searchresults['FORUM_TITLE']);
						$tpldata['searchresults'][$row]['TOPIC_TITLE'] = $this->filter_username($your_username, $my_username, $searchresults['TOPIC_TITLE']);
						$tpldata['searchresults'][$row]['POST_SUBJECT'] = $this->filter_username($your_username, $my_username, $searchresults['POST_SUBJECT']);
					}
				}
			
				if (!empty($tpldata['jumpbox_forums']))
				{			
					foreach ($tpldata['jumpbox_forums'] as $row => $jumpbox_forums)
					{
						$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = $founder_name;

						$tpldata['jumpbox_forums'][$row]['FORUM_NAME'] = $this->filter_username($your_username, $my_username, $jumpbox_forums['FORUM_NAME']);
					}
				}
				
			break;
			
			case 'viewforum':
			case 'index':
				
				if (!empty($tpldata['navlinks']))
				{
					foreach ($tpldata['navlinks'] as $row => $navlinks)
					{
						$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = $founder_name;

						$tpldata['navlinks'][$row]['FORUM_NAME'] = $this->filter_username($your_username, $my_username, $navlinks['FORUM_NAME']);
					}
				}
				
				if (!empty($tpldata['topicrow']))
				{
					foreach ($tpldata['topicrow'] as $row => $topicrow)
					{
						$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = $topicrow['TOPIC_AUTHOR'];

						$tpldata['topicrow'][$row]['TOPIC_TITLE'] = $this->filter_username($your_username, $my_username, $topicrow['TOPIC_TITLE']);
						$tpldata['topicrow'][$row]['LAST_POST_SUBJECT'] = $this->filter_username($your_username, $my_username, $topicrow['LAST_POST_SUBJECT']);
					}
				}

				if (!empty($tpldata['forumrow']))
				{
					foreach ($tpldata['forumrow'] as $row => $forumrow)
					{
						$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = $founder_name;
						
						$your_userfull = get_username_string('full', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_userfull = $founder_full;

						$tpldata['forumrow'][$row]['FORUM_DESC'] = $this->filter_username($your_userfull, $my_userfull, $forumrow['FORUM_DESC']);
						$tpldata['forumrow'][$row]['FORUM_NAME'] = $this->filter_username($your_username, $my_username, $forumrow['FORUM_NAME']);
						if(isset($forumrow['S_SUBFORUMS']))
						{
							$tpldata['forumrow'][$row]['SUBFORUMS'] = $this->filter_username($your_username, $my_username, $forumrow['SUBFORUMS']);
						}
						$tpldata['forumrow'][$row]['LAST_POST_SUBJECT'] = $this->filter_username($your_username, $my_username, $forumrow['LAST_POST_SUBJECT']);
						$tpldata['forumrow'][$row]['LAST_POST_SUBJECT_TRUNCATED'] = $this->filter_username($your_username, $my_username, $forumrow['LAST_POST_SUBJECT_TRUNCATED']);
					}
				}

				if (isset($tpldata['.'][0]['FORUM_NAME']))
				{
					$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
					$my_username = $founder_name;
						
					$tpldata['.'][0]['FORUM_NAME'] = $this->filter_username($your_username, $my_username, $tpldata['.'][0]['FORUM_NAME']);
				}
				
				if (!empty($tpldata['jumpbox_forums']))
				{			
					foreach ($tpldata['jumpbox_forums'] as $row => $jumpbox_forums)
					{
						$your_username = get_username_string('username', $this->user->data['user_id'], $this->user->data['username'], $this->user->data['user_colour'], $user->lang['GUEST']);
						$my_username = $founder_name;

						$tpldata['jumpbox_forums'][$row]['FORUM_NAME'] = $this->filter_username($your_username, $my_username, $jumpbox_forums['FORUM_NAME']);
					}
				}

			break;
		}
	}
	
	public function filter_username($your_name, $my_name, $filtered_message)
	{
		$this->user->add_lang_ext('mickroz/ymwus', 'common');
		
		$page_name = substr($this->user->page['page_name'], 0, strpos($this->user->page['page_name'], '.'));
		
		if ($page_name == 'viewtopic' || $page_name == 'mcp')
		{
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
					$quoted_cleaned = strstr($quoted_name, $this->user->lang['WROTE'], true);
					$my_name = $quoted_cleaned;
				}
				else
				{
					$my_name = $my_name;
				}
			}
			else
			{
				$my_name = $my_name;
			}
		}
		else
		{
			$my_name = $my_name;
		}
		
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
}
