# System Scaling Guide

## Current Capacity
- **50-100 concurrent users** with default XAMPP configuration

## Scaling to 500-1000+ Users

### 1. Database Optimization
```sql
-- Add missing indexes
CREATE INDEX idx_messages_conversation ON messages(sender_id, receiver_id, created_at);
CREATE INDEX idx_grades_student_term ON grades(student_id, term, academic_year);
CREATE INDEX idx_attendance_user_date ON attendance(user_id, date);
```

### 2. PHP Configuration (php.ini)
```ini
max_execution_time = 300
memory_limit = 256M
max_input_time = 300
post_max_size = 50M
upload_max_filesize = 50M

; Increase PHP-FPM workers
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 15
```

### 3. MySQL Configuration (my.ini)
```ini
max_connections = 500
innodb_buffer_pool_size = 2G
query_cache_size = 64M
query_cache_limit = 2M
thread_cache_size = 50
table_open_cache = 4000
```

### 4. Replace SSE with WebSocket
- Use Ratchet or Socket.IO for real-time features
- Reduces server load significantly

### 5. Implement Caching
- Redis/Memcached for session storage
- Cache frequently accessed data (classes, subjects, user lists)

### 6. Production Server Requirements
- **500 users**: 4 CPU cores, 8GB RAM, SSD storage
- **1000+ users**: 8 CPU cores, 16GB RAM, load balancer, separate DB server

### 7. Code Optimizations Needed
- Connection pooling
- Lazy loading for large datasets
- Pagination on all list views
- Background jobs for heavy operations (promotions, reports)
- CDN for static assets
