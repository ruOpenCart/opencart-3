<?php
class ControllerExtensionExtensionShipping extends Controller {
    private array $error = [];

    public function index(): void {
        $this->load->language('extension/extension/shipping');

        // Extensions
        $this->load->model('setting/extension');

        $this->getList();
    }

    public function install(): void {
        $this->load->language('extension/extension/shipping');

        // Extensions
        $this->load->model('setting/extension');

        if ($this->validate()) {
            $this->model_setting_extension->install('shipping', $this->request->get['extension']);

            // User Groups
            $this->load->model('user/user_group');

            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/shipping/' . $this->request->get['extension']);
            $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/shipping/' . $this->request->get['extension']);

            // Call install method if it exsits
            $this->load->controller('extension/shipping/' . $this->request->get['extension'] . '/install');

            $this->session->data['success'] = $this->language->get('text_success');
        }

        $this->getList();
    }

    public function uninstall(): void {
        $this->load->language('extension/extension/shipping');

        // Extensions
        $this->load->model('setting/extension');

        if ($this->validate()) {
            $this->model_setting_extension->uninstall('shipping', $this->request->get['extension']);

            // Call uninstall method if it exsits
            $this->load->controller('extension/shipping/' . $this->request->get['extension'] . '/uninstall');

            $this->session->data['success'] = $this->language->get('text_success');
        }

        $this->getList();
    }

    protected function getList() {
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        // Extensions
        $this->load->model('setting/extension');

        $extensions = $this->model_setting_extension->getExtensionsByType('shipping');

        foreach ($extensions as $key => $value) {
            if (!is_file(DIR_APPLICATION . 'controller/extension/shipping/' . $value . '.php') && !is_file(DIR_APPLICATION . 'controller/shipping/' . $value . '.php')) {
                $this->model_setting_extension->uninstall('shipping', $value);

                unset($extensions[$key]);
            }
        }

        $data['extensions'] = [];

        // Compatibility code for old extension folders
        $files = glob(DIR_APPLICATION . 'controller/extension/shipping/*.php');

        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');

                $this->load->language('extension/shipping/' . $extension, 'extension');

                $data['extensions'][] = [
                    'name'       => $this->language->get('extension')->get('heading_title'),
                    'status'     => $this->config->get('shipping_' . $extension . '_status') ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                    'sort_order' => $this->config->get('shipping_' . $extension . '_sort_order'),
                    'install'    => $this->url->link('extension/extension/shipping/install', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
                    'uninstall'  => $this->url->link('extension/extension/shipping/uninstall', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
                    'installed'  => in_array($extension, $extensions),
                    'edit'       => $this->url->link('extension/shipping/' . $extension, 'user_token=' . $this->session->data['user_token'], true)
                ];
            }
        }

        $data['promotion'] = $this->load->controller('extension/extension/promotion');

        $this->response->setOutput($this->load->view('extension/extension/shipping', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/extension/shipping')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}