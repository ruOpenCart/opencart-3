<?php
class ControllerExtensionTotalKlarnaFee extends Controller {
    private array $error = [];

    public function index(): void {
        $this->load->language('extension/total/klarna_fee');

        $this->document->setTitle($this->language->get('heading_title'));

        // Settings
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $status = false;

            foreach ($this->request->post['klarna_fee'] as $klarna_account) {
                if ($klarna_account['status']) {
                    $status = true;
                    break;
                }
            }

            $this->model_setting_setting->editSetting('total_klarna_fee', array_merge($this->request->post, ['klarna_fee_status' => $status]));

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true));
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
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/total/klarna_fee', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['action'] = $this->url->link('extension/total/klarna_fee', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=total', true);

        $data['countries'] = [];

        $data['countries'][] = [
            'name' => $this->language->get('text_germany'),
            'code' => 'DEU'
        ];

        $data['countries'][] = [
            'name' => $this->language->get('text_netherlands'),
            'code' => 'NLD'
        ];

        $data['countries'][] = [
            'name' => $this->language->get('text_denmark'),
            'code' => 'DNK'
        ];

        $data['countries'][] = [
            'name' => $this->language->get('text_sweden'),
            'code' => 'SWE'
        ];

        $data['countries'][] = [
            'name' => $this->language->get('text_norway'),
            'code' => 'NOR'
        ];

        $data['countries'][] = [
            'name' => $this->language->get('text_finland'),
            'code' => 'FIN'
        ];

        if (isset($this->request->post['total_klarna_fee'])) {
            $data['total_klarna_fee'] = $this->request->post['total_klarna_fee'];
        } else {
            $data['total_klarna_fee'] = $this->config->get('total_klarna_fee');
        }

        // Tax Classes
        $this->load->model('localisation/tax_class');

        $data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/total/klarna_fee', $data));
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'extension/total/klarna_fee')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}