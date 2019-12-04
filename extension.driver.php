<?php

require_once(EXTENSIONS . '/roles/lib/class.rolesmanager.php');

class extension_roles_extensions extends Extension {

    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page' => '/roles/',
                'delegate' => 'hasAccess',
                'callback' => 'onHasAccess'
            ),
            array(
                'page' => '/roles/',
                'delegate' => 'preSave',
                'callback' => 'onRolePreSave'
            ),
            array(
                'page' => '/roles/',
                'delegate' => 'appendUI',
                'callback' => 'onAppendUI'
            ),
        );
    }

    public function onRolePreSave($context)
    {
        if (empty($context['data']['extensions'])) {
            $context['data']['extensions'] = array();
        }
    }

    public function onAppendUI($context)
    {
        $role = $context['role'];

        if (empty($role)) {
            $role = array(
                'extensions' => array()
            );
        }

        // Extensions
        $fieldset = new XMLElement('fieldset', null, array('class' => 'settings'));
        $fieldset->appendChild(new XMLElement('legend', __('Extensions')));
        $div = new XMLElement('div', null, array('class' => 'two columns'));
        $fieldset->appendChild($div);

        $extensionsOptions = array(
            array('*', in_array('*', $role['extensions']), '*')
        );

        foreach(ExtensionManager::listInstalledHandles() as $extension) {

            if ($extension === 'roles') {
                continue;
            }

            if (is_dir(EXTENSIONS . '/' . $extension . '/content')) {
                $about = ExtensionManager::about($extension);
                $contents = scandir(EXTENSIONS . '/' . $extension . '/content');

                foreach ($contents as $content) {
                    if ($content === '.' || $content === '..') {
                        continue;
                    }

                    $contentKey = str_replace('.php', '', str_replace('content.', '', $content));
                    $readableValue = $about['name'] . ' - ' . $contentKey;
                    $value = $extension . '|' . $contentKey;
                    $extensionsOptions[] = array($value, in_array($value, $role['extensions']), $readableValue);
                }
            }
        }

        $label = Widget::Label('Extensions', null, 'column');
        $label->appendChild(Widget::Select('fields[extensions][]', $extensionsOptions, array('multiple' => '')));
        $div->appendChild($label);

        $context['form']->appendChild($fieldset);
    }

    public function onHasAccess($context) {
        $pageURL = !empty($context['page']) && $context['delegate'] !== 'CanAccessPage' ? $context['callback']['pageroot'] : $context['page_url'];
        preg_match_all('/extension\/([A-z0-9]*)\/([A-z0-9]*)/', $pageURL, $matches, PREG_SET_ORDER, 0);
        $extensionHandle = $matches[0][1];
        $extensionContent = empty($matches[0][2]) ? 'index' : $matches[0][2];

        if (!empty($extensionHandle) && !empty($extensionContent)) {
            foreach ($context['roles'] as $key => $role) {
                if (in_array('*', $role['extensions'], true)) {
                    $context['allowed'] =  true;
                    break;
                }

                if (in_array($extensionHandle . '|' . $extensionContent, $role['extensions'], true)) {
                    $context['allowed'] =  true;
                    break;
                }
            }

            // todo log if not allowed
        }
    }

}
