FROM nginx:alpine

# Copy custom nginx configuration
COPY nginx.conf /etc/nginx/nginx.conf
COPY conf.d /etc/nginx/conf.d

# Create user for nginx
RUN addgroup -g 1000 www && \
    adduser -D -u 1000 -G www www

# Expose ports
EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"]
