export const  ADMIN_NAVIGATION = {
  dashboard: '/admin/dashboard',
  users: '/admin/users',
  userDetails: (id: number) => ['/admin/users', id],
  import: '/admin/import'
}
