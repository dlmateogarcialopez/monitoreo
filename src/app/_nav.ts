import { INavData } from '@coreui/angular';

export const navItems: INavData[] = [
  {
    title: true,
    name: 'Difusión'
  },
  {
    name: 'Bajo demanda',
    url: '/dod',
    icon: 'icon-speech'
  },
  {
    title: true,
    name: 'Reportes',
  },
  {
    name: 'Monitoreo',
    // url: '/lucy',
    icon: 'fas fa-chart-pie',
    children: [
      {
        name: 'General',
        url: '/monitoreo/general',
        icon: 'fas fa-book'
      },
      {
        name: 'Lucy',
        icon: 'fas fa-headphones',
        children: [
          {
            name: 'Dashboard',
            url: '/monitoreo/lucy',
            icon: 'fas fa-list',
          },
          {
            name: 'Falta de energía',
            url: '/monitoreo/falta-energia',
            icon: 'fas fa-bolt',
          },
          {
            name: 'Copia de factura',
            url: '/monitoreo/copia-factura',
            icon: 'fas fa-copy',
          },
        ]
      },
      {
        name: 'DINP',
        url: '/monitoreo/dinp',
        icon: 'fas fa-robot'
      },
      {
        name: 'Seguimiento individual',
        url: '/monitoreo/seguimiento',
        icon: 'fas fa-search',
        class: "seguimiento",
      },
      {
        name: 'Reportes',
        url: '/monitoreo/reportes',
        icon: 'fas fa-file-download'
      },
      {
        name: 'Contact Center',
        url: '/monitoreo/contact-center',
        icon: 'fas fa-phone'
      }
    ]
  },
];
