<?php
// https://icon-sets.iconify.design/
$iconset = [
    // entidades
    'app' => 'heroicons-solid:puzzle',
    'user' => 'fa-solid:user-friends',
    'agent' => 'fa-solid:user-friends',
    'agent-1' => 'fa-solid:user',
    'agent-2' => 'fa-solid:user-friends',
  
    'space' => 'clarity:building-solid',
    'event' => 'bxs:calendar-event',
    'project' => 'ri:file-list-2-fill',
    'opportunity' => 'mdi:lightbulb-on',

    // redes sociais
    'facebook' => 'brandico:facebook',
    'github' => 'la:github-alt',
    'instagram' => 'fa6-brands:instagram',
    'linkedin' => 'akar-icons:linkedin-box-fill',
    'pinterest' => 'fa6-brands:pinterest-p',
    'spotify' => 'akar-icons:spotify-fill',
    'telegram' => 'cib:telegram-plane',
    'twitter' => 'akar-icons:twitter-fill',
    'whatsapp' => 'akar-icons:whatsapp-fill',
    'vimeo' => 'brandico:vimeo',
    'youtube' => 'akar-icons:youtube-fill',


    // IMPORTANTE: manter ordem alfabética
    'access' => 'ooui:previous-rtl',
    'account' => 'mdi:gear',
    'add' => 'ps:plus',
    'agents' => 'fluent:people-32-regular',
    'agent-single' => 'basil:user-outline',
    'app' => 'heroicons-solid:puzzle',
    'archive' => 'mi:archive',
    'arrow-down' => 'akar-icons:arrow-down',
    'arrow-left-ios' => 'material-symbols:arrow-back-ios',
    'arrow-left' => 'akar-icons:arrow-left',
    'arrow-right-ios' => 'material-symbols:arrow-forward-ios',
    'arrow-right' => 'akar-icons:arrow-right',
    'arrow-up' => 'akar-icons:arrow-up',
    'arrowPoint-down' => 'fe:arrow-down',
    'arrowPoint-left' => 'fe:arrow-left',
    'arrowPoint-right' => 'fe:arrow-right',
    'attachment' => 'mdi:paperclip',
    'arrowPoint-up' => 'fe:arrow-up',
    'check' => 'material-symbols:check-circle',
    'circle' => 'material-symbols:circle',
    'circle-checked' => 'material-symbols:check-circle-rounded',
    'clock' => 'fluent:clipboard-clock-20-filled',
    'close' => 'gg:close',
    'closed' => 'mdi:close',
    'code' => 'fa-solid:code',
    'columns' => 'fluent:column-triple-24-filled',
    'columns-edit' => 'fluent:column-triple-edit-24-filled',
    'copy' => 'ic:baseline-content-copy',
    'dashboard' => 'ic:round-dashboard',
    'date'=> 'material-symbols:date-range-rounded',
    'delete' => 'gg:close',
    'dot' => 'prime:circle-fill',
    'down' => 'mdi:chevron-down',
    'download' => 'el:download-alt',
    'edit' => 'zondicons:edit-pencil',
    'error' => 'material-symbols:chat-error-sharp',
    'exchange' => 'material-symbols:change-circle-outline',
    'exclamation' => 'ant-design:exclamation-circle-filled',
    'external'  =>  'charm:link-external',
    'eye-view' => 'ic:baseline-remove-red-eye',
    'favorite' => 'mdi:star-outline',
    'filter' => 'ic:baseline-filter-alt',
    'file' => 'bx:file',
    'help' => 'ic:baseline-help',
    'help-outline' => 'material-symbols:help-outline',
    'history' => 'material-symbols:history',
    'home' => 'ci:home-fill',
    'image' => 'bi:image-fill',
    'info-full' => 'material-symbols:info-rounded',
    'info' => 'material-symbols:info-outline-rounded',
    'lamp' => 'mdi:lightbulb-on-outline',
    'link' => 'cil:link-alt',
    'list' => 'ci:list-ul',
    'loading' => 'eos-icons:three-dots-loading',
    'login' => 'icon-park-outline:login',
    'logout' => 'ri:logout-box-line',
    'magnifier' => 'simple-line-icons:magnifier',
    'map-pin' => 'charm:map-pin',
    'map' => 'bxs:map-alt',
    'menu-mobile' => 'icon-park-outline:hamburger-button',
    'network' => 'grommet-icons:connect',
    'next' => 'ooui:previous-rtl',
    'notification' => 'eva:bell-outline',
    'order-down' => 'heroicons-outline:sort-descending',
    'order-up' => 'heroicons-outline:sort-ascending',
    'pin' => 'ph:map-pin-fill',
    'previous' => 'ooui:previous-ltr',
    'print' => 'material-symbols:print-outline',
    'projects' => 'ri:file-list-2-line',
    'process' => 'fluent-mdl2:processing-pause',
    'question' => 'fe:question',
    'role'  => 'ri:profile-line',
    'seal' => 'mdi:seal-variant',
    'search' => 'ant-design:search-outlined',
    'selected' => 'grommet-icons:radial-selected',
    'send' => 'ic:sharp-send',
    'settings' => 'bxs:cog',
    'sort' => 'mdi:sort',
    'spaces' => 'clarity:building-line',
    'sync' => 'material-symbols:sync',
    'text' => 'ic:sharp-text-fields',
    'ticket' => 'mdi:ticket-confirmation-outline',  
    'trash' => 'ooui:trash',
    'triangle-down' => 'entypo:triangle-down',
    'triangle-up' => 'entypo:triangle-up',
    'up' => 'mdi:chevron-up',
    'upload' => 'ic:baseline-file-upload',
    'user-config' => 'fa-solid:users-cog',

];

$app->applyHook('component(mc-icon).iconset', [&$iconset]);

$this->jsObject['config']['iconset'] = $iconset;