<?php
class ControllerMarketplaceExtension extends Controller {
    private array $error = [];

    public function index(): void {
        $this->load->language('marketplace/extension');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true)
        ];

        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->request->get['type'])) {
            $data['type'] = $this->request->get['type'];
        } else {
            $data['type'] = '';
        }

        $data['categories'] = [];

        $files = glob(DIR_APPLICATION . 'controller/extension/extension/*.php', GLOB_BRACE);

        foreach ($files as $file) {
            $extension = basename($file, '.php');

            // Compatibility code for old extension folders
            $this->load->language('extension/extension/' . $extension, 'extension');

            if ($this->user->hasPermission('access', 'extension/extension/' . $extension)) {
                $files = glob(DIR_APPLICATION . 'controller/extension/' . $extension . '/*.php', GLOB_BRACE);

                $data['categories'][] = [
                    'code' => $extension,
                    'text' => $this->language->get('extension')->get('heading_title') . ' (' . count($files) . ')',
                    'href' => $this->url->link('extension/extension/' . $extension, 'user_token=' . $this->session->data['user_token'], true)
                ];
            }
        }

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('marketplace/extension', $data));
    }
}