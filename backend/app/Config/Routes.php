<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Default route
$routes->get('/', 'Home::index');

// Authentication routes
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attemptLogin');
$routes->get('register', 'Auth::register');
$routes->post('register', 'Auth::attemptRegister');
$routes->get('logout', 'Auth::logout');

// Superadmin
$routes->group('sa', ['filter' => 'auth'], function($routes) {
    $routes->get('dashboard', 'SAController::index');
    
    // TSR Management
    $routes->post('create-tsr', 'SAController::createTsr');
    $routes->post('admin/tsr/create', 'SAController::createTsr');
    $routes->get('tsr-accounts', 'SAController::tsrAccounts');

    // Client Management
    $routes->get('client-accounts', 'SAController::clients');
    $routes->post('create-client', 'SAController::createClient');  
    $routes->get('client/delete/(:num)', 'SAController::deleteClient/$1');

    // Ticket Logs
    $routes->get('ticket-logs', 'SAController::ticketLogs'); 
    $routes->get('tickets', 'SAController::ticketLogs'); 
    $routes->get('tickets/export', 'SAController::exportTickets');
    
    // CHAT / GROUP MANAGER
    $routes->get('chat-manager', 'SAController::chatManager'); 
    $routes->post('chat/create-group', 'SAController::createChatGroup');
    $routes->post('chat/delete-group', 'SAController::deleteChatGroup');

    $routes->get('chat/get-members/(:num)', 'SAController::getGroupMembers/$1');
    $routes->get('chat/search-users', 'SAController::searchChatUsers'); 
    $routes->post('chat/add-member', 'SAController::addGroupMember');
    $routes->post('chat/remove-member', 'SAController::removeGroupMember');
    $routes->post('chat/add-members-batch', 'SAController::addMembersBatch');
    // ==========================================================

    // Category Management
    $routes->post('categories/save', 'SAController::saveCategory');
    $routes->post('categories/toggle', 'SAController::toggleCategory');
    $routes->post('categories/delete', 'SAController::deleteCategory'); 

    // Knowledge Base / Feedback
    $routes->get('feedback', 'SAController::feedback'); 
    $routes->post('knowledge-base/save', 'SAController::saveKbEntry');
    $routes->get('knowledge-base/delete/(:num)', 'SAController::deleteKbEntry/$1');

    // System
    $routes->get('audit-trail', 'SAController::auditTrail');
    $routes->get('backups', 'SAController::backups');
});
// Chat routes (protected)
$routes->group('chat', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Chat::index');
    $routes->get('users', 'Chat::users');
    $routes->get('groups', 'Chat::groups');
    $routes->get('direct/(:num)', 'Chat::directChat/$1');
    $routes->get('group/(:num)', 'Chat::groupChat/$1');
    $routes->post('createGroup', 'Chat::createGroup');
    $routes->post('updateGroup/(:num)', 'Chat::updateGroup/$1');
    $routes->post('updateProfile', 'Chat::updateProfile');
    $routes->post('mark_as_read', 'Chat::mark_as_read');
    $routes->post('delete_message', 'Chat::delete_message');
});

// --- FIX START: MOVED CALLS OUTSIDE OF API GROUP ---
// Call Routes (Audio/Video)
$routes->group('calls', ['filter' => 'auth'], function($routes) {
    $routes->get('audio/(:num)', 'Calls::audio/$1');
    $routes->get('video/(:num)', 'Calls::video/$1');
});
// --- FIX END ---

// API routes
$routes->group('api', function($routes) {
    // User API
    $routes->post('users/create', 'ApiController::createUser');
    $routes->get('users/list', 'ApiController::getUsers');

    // Add this to app/Config/Routes.php
    $routes->group('api', function($routes) {
    $routes->post('updateUserStatus', '\App\Controllers\UserController::updateStatus');
});
    
    // Message API
    $routes->post('messages/send', 'ApiController::sendMessage');
    $routes->get('messages/(:num)', 'ApiController::getMessages/$1');
    $routes->post('messages/seen', 'ApiController::markAsSeen');
    
    // Group API
    $routes->post('groups/create', 'ApiController::createGroup');
    $routes->get('groups/list', 'ApiController::getGroups');
    $routes->post('groups/join', 'ApiController::joinGroup');
    $routes->post('leave-group/(:num)', 'ApiController::leaveGroup/$1');
    $routes->get('groups', 'ApiController::getGroups');
    
    // Ticket API
    $routes->get('tickets/user', 'ApiController::getUserTickets');
    $routes->post('tickets/create', 'ApiController::createTicket');
    $routes->get('tickets/stats', 'ApiController::getTicketStats');

    // Customer ticket API routes
    $routes->get('tickets/customer/(:num)/status', 'ApiController::getTicketStatus/$1');
    $routes->post('tickets/customer/send-message', 'ApiController::sendCustomerTicketMessage');
    $routes->get('tickets/customer/(:num)/messages', 'ApiController::getCustomerTicketMessages/$1');

    // TSR ticket API routes  
    $routes->post('tickets/tsr/send-message', 'TicketController::sendTicketMessage');
    
    $routes->get('tickets/(:num)/status', 'TicketController::getTicketDetails/$1'); 
    
    $routes->put('tickets/(:num)/status', 'TicketController::updateTicketStatus/$1');
    $routes->post('tickets/tsr/claim', 'TSR::claimTicket');
    
  
    
    // TSR Ticket API Details
    $routes->get('tickets/tsr/list', 'TicketController::getTSRTicketList');
    $routes->get('tickets/tsr/(:num)', 'TicketController::getTicketDetails/$1');
    $routes->get('tickets/tsr/(:num)/messages', 'TicketController::getTicketMessages/$1');
    $routes->post('tickets/tsr/(:num)/message', 'TicketController::sendTicketMessage/$1');
    $routes->put('tickets/tsr/(:num)/status', 'TicketController::updateTicketStatus/$1');

    // API routes for sidebar functionality
    $routes->get('users', 'ApiController::getUsers');
    $routes->get('getUserGroups', 'ApiController::getUserGroups');
    $routes->get('unreadNotificationsCount', 'ApiController::unreadNotificationsCount');
    $routes->get('getAllUserStatuses', 'ApiController::getAllUserStatuses');
    $routes->post('updateUserStatus', 'ApiController::updateUserStatus');
    $routes->get('api/notifications', 'Chat::getNotifications');
    $routes->post('api/markNotificationRead', 'Chat::markNotificationRead');
    $routes->get('api/unreadNotificationsCount', 'Chat::unreadNotificationsCount');

    // Message API Fetching
    $routes->get('getDirectMessages/(:num)', 'ApiController::getDirectMessages/$1');
    $routes->get('getGroupMessages/(:num)', 'ApiController::getGroupMessages/$1');
    $routes->post('saveMessage', 'ApiController::saveMessage');
    $routes->post('markMessageAsSeen', 'ApiController::markMessageAsSeen');
    $routes->get('getMessageSeenUsers/(:any)', 'ApiController::getMessageSeenUsers/$1');

    // Utility API routes
    $routes->get('testEndpoint', 'ApiController::testEndpoint');
    $routes->post('uploadFile', 'ApiController::uploadFile');
    $routes->get('checkMessageStatusTable', 'ApiController::checkMessageStatusTable');
    $routes->post('createMessageStatusTable', 'ApiController::createMessageStatusTable');
    $routes->get('getCallLogs', 'ApiController::getCallLogs');
});

// Customer ticket routes
$routes->group('tickets', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'CustomerTickets::index');
    $routes->get('(:num)/chat', 'CustomerTickets::chat/$1');
});

// TSR routes
$routes->group('tsr', ['filter' => 'tsr'], function($routes) {
    $routes->get('/', 'TSR::index');
    $routes->get('dashboard', 'TSR::dashboard');
    $routes->post('claimTicket', 'TSR::claimTicket');
    $routes->get('ticket/(:num)', 'TSR::viewTicket/$1');
    $routes->get('ticket/(:num)/chat', 'TSR::ticketChat/$1');
    $routes->get('queue', 'TSR::queue');
});

// Setup routes
$routes->get('setup', 'Setup::index');
$routes->post('setup/migrate', 'Setup::migrate');

// KB routes
$routes->get('api/knowledge-base/search', 'ApiController::searchKnowledgeBase');
$routes->post('api/knowledge-base/feedback', 'ApiController::submitKbFeedback');

