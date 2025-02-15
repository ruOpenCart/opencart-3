<?php
class ControllerExtensionModulePPBraintreeButton extends Controller {
    public function index(): void {
        $this->load->language('extension/module/pp_braintree_button');

        // Settings
        $this->load->model('setting/setting');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_pp_braintree_button', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/pp_braintree_button', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['action'] = $this->url->link('extension/module/pp_braintree_button', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);
        $data['layouts'] = $this->url->link('design/layout', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->post['module_pp_braintree_button_status'])) {
            $data['module_pp_braintree_button_status'] = $this->request->post['module_pp_braintree_button_status'];
        } else {
            $data['module_pp_braintree_button_status'] = $this->config->get('module_pp_braintree_button_status');
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/pp_braintree_button', $data));
    }

    public function install(): void {
        // Settings
        $this->load->model('setting/setting');

        $settings['module_pp_braintree_button_status'] = 1;

        $this->model_setting_setting->editSetting('module_pp_braintree_button', $settings);
    }

    public function configure(): void {
        $this->load->language('extension/extension/module');

        if (!$this->user->hasPermission('modify', 'extension/extension/module')) {
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true));
        } else {
            // Extensions
            $this->load->model('setting/extension');

            $this->load->model('user/user_group');

            $this->model_setting_extension->install('module', 'pp_braintree_button');

            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/pp_braintree_button');
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/pp_braintree_button');

            $this->install();

            $this->response->redirect($this->url->link('design/layout', 'user_token=' . $this->session->data['user_token'], true));
        }
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/pp_braintree_button')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}