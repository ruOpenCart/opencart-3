<?php
class ModelExtensionTotalTotal extends Model {
    public function getTotal(array $total): void {
        $this->load->language('extension/total/total');

        $total['totals'][] = [
            'code'       => 'total',
            'title'      => $this->language->get('text_total'),
            'value'      => max(0, $total['total']),
            'sort_order' => $this->config->get('total_total_sort_order')
        ];
    }
}