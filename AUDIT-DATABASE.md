# Database Query Performance Audit

This audit focuses on identifying and resolving database query performance bottlenecks in the API.

## Findings

The audit identified several performance issues:

1.  **Inefficient API Key Lookup:** The `ApiKey::findByPlainKey()` method loaded all potential API keys based on a prefix and then iterated through them in PHP. This was inefficient and memory-intensive, especially for legacy SHA-256 keys.
2.  **Synchronous API Usage Tracking:** The `ApiUsage::record()` method recorded API usage synchronously on every request, adding unnecessary latency to API responses.
3.  **Inefficient API Usage Aggregation:** The `ApiUsageDaily::recordFromUsage()` method used multiple database queries to update a single record, which was inefficient and not atomic.
4.  **Missing Database Indexes:** The `api_keys` and `api_usage` tables were missing indexes on columns frequently used in queries, leading to slower query performance.

## Improvements

The following improvements were made to address the identified issues:

1.  **Optimized API Key Lookup:** The `ApiKey::findByPlainKey()` method was refactored to use a more targeted database query that directly matches legacy SHA-256 hashes and eager loads the `workspace` relationship to prevent N+1 queries.
2.  **Asynchronous API Usage Tracking:** API usage recording was moved to a background job (`RecordApiUsageJob`) to improve API response times.
3.  **Efficient API Usage Aggregation:** The `ApiUsageDaily::recordFromUsage()` method was refactored to use a single, atomic `upsert` operation, reducing the number of database queries from four to one.
4.  **Added Database Indexes:** A new database migration was created to add an index to the `prefix` column on the `api_keys` table and a composite index on the `(api_key_id, created_at)` columns on the `api_usage` table.
