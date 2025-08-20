--
-- PostgreSQL database dump
--

-- Dumped from database version 16.9 (Ubuntu 16.9-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.9 (Ubuntu 16.9-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: get_zodiac_sign(date); Type: FUNCTION; Schema: public; Owner: sneaker_user
--

CREATE FUNCTION public.get_zodiac_sign(birth_date date) RETURNS character varying
    LANGUAGE plpgsql
    AS $$
            DECLARE
                month_day INTEGER;
            BEGIN
                IF birth_date IS NULL THEN
                    RETURN NULL;
                END IF;
                
                -- Convert to MMDD format for easy comparison
                month_day := EXTRACT(MONTH FROM birth_date) * 100 + EXTRACT(DAY FROM birth_date);
                
                CASE 
                    WHEN (month_day >= 219 AND month_day <= 320) THEN RETURN 'PISCES';
                    WHEN (month_day >= 321 AND month_day <= 419) THEN RETURN 'ARIES';
                    WHEN (month_day >= 420 AND month_day <= 520) THEN RETURN 'TAURUS';
                    WHEN (month_day >= 521 AND month_day <= 620) THEN RETURN 'GEMINI';
                    WHEN (month_day >= 621 AND month_day <= 722) THEN RETURN 'CANCER';
                    WHEN (month_day >= 723 AND month_day <= 822) THEN RETURN 'LEO';
                    WHEN (month_day >= 823 AND month_day <= 922) THEN RETURN 'VIRGO';
                    WHEN (month_day >= 923 AND month_day <= 1022) THEN RETURN 'LIBRA';
                    WHEN (month_day >= 1023 AND month_day <= 1121) THEN RETURN 'SCORPIO';
                    WHEN (month_day >= 1122 AND month_day <= 1221) THEN RETURN 'SAGITARIUS';
                    WHEN (month_day >= 1222 OR month_day <= 119) THEN RETURN 'CAPRICORN';
                    WHEN (month_day >= 120 AND month_day <= 218) THEN RETURN 'AQUARIUS';
                    ELSE RETURN NULL;
                END CASE;
            END;
            $$;


ALTER FUNCTION public.get_zodiac_sign(birth_date date) OWNER TO sneaker_user;

--
-- Name: update_zodiac_trigger(); Type: FUNCTION; Schema: public; Owner: sneaker_user
--

CREATE FUNCTION public.update_zodiac_trigger() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
            BEGIN
                NEW.zodiac := get_zodiac_sign(NEW.birthdate);
                RETURN NEW;
            END;
            $$;


ALTER FUNCTION public.update_zodiac_trigger() OWNER TO sneaker_user;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO sneaker_user;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO sneaker_user;

--
-- Name: categories; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.categories (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    description text,
    image character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    menu_placement character varying(255),
    secondary_menus json,
    show_in_menu boolean DEFAULT true NOT NULL,
    is_featured boolean DEFAULT false NOT NULL,
    category_keywords json,
    meta_title character varying(255),
    meta_description text,
    meta_keywords json,
    brand_color character varying(255),
    meta_data json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.categories OWNER TO sneaker_user;

--
-- Name: categories_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.categories_id_seq OWNER TO sneaker_user;

--
-- Name: categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.categories_id_seq OWNED BY public.categories.id;


--
-- Name: coupon_usages; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.coupon_usages (
    id bigint NOT NULL,
    user_id bigint,
    coupon_id bigint NOT NULL,
    order_id bigint NOT NULL,
    discount_amount numeric(12,2) NOT NULL,
    used_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.coupon_usages OWNER TO sneaker_user;

--
-- Name: coupon_usages_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.coupon_usages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.coupon_usages_id_seq OWNER TO sneaker_user;

--
-- Name: coupon_usages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.coupon_usages_id_seq OWNED BY public.coupon_usages.id;


--
-- Name: coupons; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.coupons (
    id bigint NOT NULL,
    code character varying(50) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    type character varying(255) NOT NULL,
    value numeric(10,2) NOT NULL,
    minimum_amount numeric(12,2),
    maximum_discount numeric(12,2),
    usage_limit integer,
    used_count integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    starts_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    applicable_categories json,
    applicable_products json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.coupons OWNER TO sneaker_user;

--
-- Name: coupons_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.coupons_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.coupons_id_seq OWNER TO sneaker_user;

--
-- Name: coupons_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.coupons_id_seq OWNED BY public.coupons.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO sneaker_user;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO sneaker_user;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: ginee_product_mappings; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.ginee_product_mappings (
    id bigint NOT NULL,
    product_id bigint NOT NULL,
    ginee_master_sku character varying(255) NOT NULL,
    ginee_product_id character varying(255),
    ginee_warehouse_id character varying(255),
    sync_enabled boolean DEFAULT true NOT NULL,
    stock_sync_enabled boolean DEFAULT true NOT NULL,
    price_sync_enabled boolean DEFAULT true NOT NULL,
    last_product_sync timestamp(0) without time zone,
    last_stock_sync timestamp(0) without time zone,
    last_price_sync timestamp(0) without time zone,
    stock_quantity_ginee integer,
    price_ginee numeric(15,2),
    ginee_product_data jsonb,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.ginee_product_mappings OWNER TO sneaker_user;

--
-- Name: ginee_product_mappings_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.ginee_product_mappings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ginee_product_mappings_id_seq OWNER TO sneaker_user;

--
-- Name: ginee_product_mappings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.ginee_product_mappings_id_seq OWNED BY public.ginee_product_mappings.id;


--
-- Name: ginee_sync_logs; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.ginee_sync_logs (
    id bigint NOT NULL,
    type character varying(255),
    status character varying(255) NOT NULL,
    items_processed integer DEFAULT 0 NOT NULL,
    items_successful integer DEFAULT 0 NOT NULL,
    items_failed integer DEFAULT 0 NOT NULL,
    summary json,
    error_message text,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    operation_type character varying(255),
    sku character varying(255),
    product_name character varying(255),
    old_stock integer,
    old_warehouse_stock integer,
    new_stock integer,
    new_warehouse_stock integer,
    message text,
    ginee_response jsonb,
    transaction_id character varying(255),
    method_used character varying(255),
    initiated_by_user character varying(255),
    dry_run boolean DEFAULT false NOT NULL,
    batch_size integer,
    session_id character varying(255)
);


ALTER TABLE public.ginee_sync_logs OWNER TO sneaker_user;

--
-- Name: COLUMN ginee_sync_logs.type; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.ginee_sync_logs.type IS 'Type of sync operation: manual, auto, bulk, webhook, etc';


--
-- Name: COLUMN ginee_sync_logs.status; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.ginee_sync_logs.status IS 'Status: success, failed, pending, skipped, etc';


--
-- Name: COLUMN ginee_sync_logs.operation_type; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.ginee_sync_logs.operation_type IS 'Operation: dashboard_manual, quick_dashboard, smart_sync, etc';


--
-- Name: COLUMN ginee_sync_logs.sku; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.ginee_sync_logs.sku IS 'Product SKU being synced';


--
-- Name: COLUMN ginee_sync_logs.ginee_response; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.ginee_sync_logs.ginee_response IS 'Full response from Ginee API (JSONB)';


--
-- Name: COLUMN ginee_sync_logs.session_id; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.ginee_sync_logs.session_id IS 'Session identifier for grouping related operations';


--
-- Name: ginee_sync_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.ginee_sync_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.ginee_sync_logs_id_seq OWNER TO sneaker_user;

--
-- Name: ginee_sync_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.ginee_sync_logs_id_seq OWNED BY public.ginee_sync_logs.id;


--
-- Name: google_sheets_sync_logs; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.google_sheets_sync_logs (
    id bigint NOT NULL,
    sync_id character varying(255) NOT NULL,
    spreadsheet_id character varying(255) NOT NULL,
    sheet_name character varying(255) DEFAULT 'Sheet1'::character varying NOT NULL,
    initiated_by character varying(255),
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    total_rows integer DEFAULT 0 NOT NULL,
    processed_rows integer DEFAULT 0 NOT NULL,
    created_products integer DEFAULT 0 NOT NULL,
    updated_products integer DEFAULT 0 NOT NULL,
    deleted_products integer DEFAULT 0 NOT NULL,
    skipped_rows integer DEFAULT 0 NOT NULL,
    error_count integer DEFAULT 0 NOT NULL,
    unique_sku_parents integer DEFAULT 0 NOT NULL,
    unique_skus integer DEFAULT 0 NOT NULL,
    products_with_variants integer DEFAULT 0 NOT NULL,
    sync_results json,
    error_details json,
    sync_options json,
    sku_mapping json,
    duration_seconds integer,
    summary text,
    error_message text,
    sync_strategy character varying(255) DEFAULT 'individual_sku'::character varying NOT NULL,
    clean_old_data boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.google_sheets_sync_logs OWNER TO sneaker_user;

--
-- Name: TABLE google_sheets_sync_logs; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON TABLE public.google_sheets_sync_logs IS 'Logs for Google Sheets sync operations with detailed metrics';


--
-- Name: COLUMN google_sheets_sync_logs.deleted_products; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.google_sheets_sync_logs.deleted_products IS 'Number of products deleted during smart sync';


--
-- Name: COLUMN google_sheets_sync_logs.unique_sku_parents; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.google_sheets_sync_logs.unique_sku_parents IS 'Number of unique SKU parent products in spreadsheet';


--
-- Name: COLUMN google_sheets_sync_logs.unique_skus; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.google_sheets_sync_logs.unique_skus IS 'Number of unique individual SKUs processed';


--
-- Name: COLUMN google_sheets_sync_logs.sync_strategy; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.google_sheets_sync_logs.sync_strategy IS 'Strategy used: individual_sku, grouped_variants, smart_individual_sku';


--
-- Name: google_sheets_sync_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.google_sheets_sync_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.google_sheets_sync_logs_id_seq OWNER TO sneaker_user;

--
-- Name: google_sheets_sync_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.google_sheets_sync_logs_id_seq OWNED BY public.google_sheets_sync_logs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO sneaker_user;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO sneaker_user;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO sneaker_user;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: menu_navigation; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.menu_navigation (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    url character varying(255),
    route_name character varying(255),
    icon character varying(255),
    description text,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    is_external boolean DEFAULT false NOT NULL,
    target character varying(255) DEFAULT '_self'::character varying NOT NULL,
    parent_id bigint,
    permissions json,
    meta_data json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.menu_navigation OWNER TO sneaker_user;

--
-- Name: menu_navigation_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.menu_navigation_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.menu_navigation_id_seq OWNER TO sneaker_user;

--
-- Name: menu_navigation_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.menu_navigation_id_seq OWNED BY public.menu_navigation.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO sneaker_user;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO sneaker_user;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: order_items; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.order_items (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    product_id bigint,
    product_name character varying(255) NOT NULL,
    product_sku character varying(255),
    product_price numeric(12,2) NOT NULL,
    quantity integer NOT NULL,
    total_price numeric(15,2) NOT NULL,
    product_snapshot json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.order_items OWNER TO sneaker_user;

--
-- Name: order_items_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.order_items_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.order_items_id_seq OWNER TO sneaker_user;

--
-- Name: order_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.order_items_id_seq OWNED BY public.order_items.id;


--
-- Name: orders; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.orders (
    id bigint NOT NULL,
    order_number character varying(100) NOT NULL,
    user_id bigint,
    customer_name character varying(255),
    customer_email character varying(255),
    customer_phone character varying(255),
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    subtotal numeric(12,2) NOT NULL,
    tax_amount numeric(12,2) NOT NULL,
    shipping_cost numeric(12,2) NOT NULL,
    discount_amount numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    total_amount numeric(12,2) NOT NULL,
    currency character(3) DEFAULT 'IDR'::bpchar NOT NULL,
    shipping_address json NOT NULL,
    billing_address json NOT NULL,
    store_origin json,
    payment_method character varying(255),
    payment_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    payment_token text,
    payment_url character varying(255),
    tracking_number character varying(255),
    shipped_at timestamp(0) without time zone,
    delivered_at timestamp(0) without time zone,
    notes text,
    meta_data json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    shipping_destination_id character varying(255),
    shipping_destination_label text,
    shipping_postal_code character varying(10),
    snap_token text,
    payment_response jsonb,
    coupon_id bigint,
    coupon_code character varying(50),
    points_used integer DEFAULT 0 NOT NULL,
    points_discount numeric(10,2) DEFAULT '0'::numeric NOT NULL
);


ALTER TABLE public.orders OWNER TO sneaker_user;

--
-- Name: orders_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.orders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.orders_id_seq OWNER TO sneaker_user;

--
-- Name: orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.orders_id_seq OWNED BY public.orders.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO sneaker_user;

--
-- Name: points_transactions; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.points_transactions (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    order_id bigint,
    type character varying(255) NOT NULL,
    amount numeric(15,2) NOT NULL,
    description text,
    reference character varying(255),
    balance_before numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    balance_after numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT points_transactions_type_check CHECK (((type)::text = ANY ((ARRAY['earned'::character varying, 'redeemed'::character varying, 'expired'::character varying, 'adjustment'::character varying])::text[])))
);


ALTER TABLE public.points_transactions OWNER TO sneaker_user;

--
-- Name: TABLE points_transactions; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON TABLE public.points_transactions IS 'Track all points transactions for users';


--
-- Name: COLUMN points_transactions.type; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.points_transactions.type IS 'Type of transaction: earned, redeemed, expired, adjustment';


--
-- Name: COLUMN points_transactions.amount; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.points_transactions.amount IS 'Points amount (positive for earned, negative for redeemed)';


--
-- Name: COLUMN points_transactions.reference; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.points_transactions.reference IS 'Reference like order number, redemption code, etc.';


--
-- Name: COLUMN points_transactions.balance_before; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.points_transactions.balance_before IS 'User points balance before this transaction';


--
-- Name: COLUMN points_transactions.balance_after; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.points_transactions.balance_after IS 'User points balance after this transaction';


--
-- Name: points_transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.points_transactions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.points_transactions_id_seq OWNER TO sneaker_user;

--
-- Name: points_transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.points_transactions_id_seq OWNED BY public.points_transactions.id;


--
-- Name: products; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.products (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    short_description text,
    description text,
    category_id bigint NOT NULL,
    brand character varying(255),
    sku character varying(255),
    gender_target jsonb,
    product_type character varying(255),
    price numeric(12,2) NOT NULL,
    sale_price numeric(12,2),
    stock_quantity integer DEFAULT 0 NOT NULL,
    min_stock_level integer DEFAULT 5 NOT NULL,
    weight numeric(8,2),
    images json,
    features json,
    specifications json,
    available_sizes json,
    available_colors json,
    is_active boolean DEFAULT true NOT NULL,
    is_featured boolean DEFAULT false NOT NULL,
    is_featured_sale boolean DEFAULT false NOT NULL,
    published_at timestamp(0) without time zone,
    sale_start_date date,
    sale_end_date date,
    search_keywords json,
    meta_title character varying(255),
    meta_description text,
    meta_keywords json,
    dimensions json,
    meta_data json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    sku_parent character varying(255),
    length numeric(8,2),
    width numeric(8,2),
    height numeric(8,2),
    ginee_last_sync timestamp(0) without time zone,
    ginee_sync_status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    ginee_data json,
    ginee_id character varying(255),
    ginee_product_id character varying(255),
    ginee_sync_error text,
    warehouse_stock integer,
    ginee_last_stock_sync timestamp(0) without time zone,
    ginee_last_stock_push timestamp(0) without time zone,
    CONSTRAINT products_ginee_sync_status_check CHECK (((ginee_sync_status)::text = ANY ((ARRAY['pending'::character varying, 'synced'::character varying, 'error'::character varying])::text[])))
);


ALTER TABLE public.products OWNER TO sneaker_user;

--
-- Name: COLUMN products.warehouse_stock; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.products.warehouse_stock IS 'Stock di warehouse Ginee';


--
-- Name: COLUMN products.ginee_last_stock_sync; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.products.ginee_last_stock_sync IS 'Terakhir sync stock dari Ginee';


--
-- Name: COLUMN products.ginee_last_stock_push; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.products.ginee_last_stock_push IS 'Terakhir push stock ke Ginee';


--
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.products_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.products_id_seq OWNER TO sneaker_user;

--
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO sneaker_user;

--
-- Name: shopping_cart; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.shopping_cart (
    id bigint NOT NULL,
    user_id bigint,
    session_id character varying(255),
    product_id bigint NOT NULL,
    quantity integer NOT NULL,
    product_options json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.shopping_cart OWNER TO sneaker_user;

--
-- Name: shopping_cart_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.shopping_cart_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.shopping_cart_id_seq OWNER TO sneaker_user;

--
-- Name: shopping_cart_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.shopping_cart_id_seq OWNED BY public.shopping_cart.id;


--
-- Name: user_addresses; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.user_addresses (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    label character varying(255),
    recipient_name character varying(255) NOT NULL,
    phone_recipient character varying(255) NOT NULL,
    province_name character varying(255) NOT NULL,
    city_name character varying(255) NOT NULL,
    subdistrict_name character varying(255) NOT NULL,
    postal_code character varying(10) NOT NULL,
    destination_id character varying(255),
    street_address text NOT NULL,
    notes text,
    is_primary boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.user_addresses OWNER TO sneaker_user;

--
-- Name: user_addresses_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.user_addresses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_addresses_id_seq OWNER TO sneaker_user;

--
-- Name: user_addresses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.user_addresses_id_seq OWNED BY public.user_addresses.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255),
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    google_id character varying(255),
    avatar text,
    total_spent numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    total_orders integer DEFAULT 0 NOT NULL,
    spending_updated_at timestamp(0) without time zone,
    customer_tier character varying(20) DEFAULT 'basic'::character varying NOT NULL,
    phone character varying(255),
    gender character varying(255),
    birthdate date,
    zodiac character varying(20),
    spending_6_months numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    tier_period_start timestamp(0) without time zone,
    last_tier_evaluation timestamp(0) without time zone,
    points_balance numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    total_points_earned numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    total_points_redeemed numeric(15,2) DEFAULT '0'::numeric NOT NULL,
    CONSTRAINT users_customer_tier_check CHECK (((customer_tier)::text = ANY ((ARRAY['basic'::character varying, 'advance'::character varying, 'ultimate'::character varying])::text[]))),
    CONSTRAINT users_gender_check CHECK (((gender)::text = ANY ((ARRAY['mens'::character varying, 'womens'::character varying, 'kids'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO sneaker_user;

--
-- Name: COLUMN users.total_spent; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.users.total_spent IS 'Total amount spent by user from paid orders';


--
-- Name: COLUMN users.total_orders; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.users.total_orders IS 'Total number of paid orders by user';


--
-- Name: COLUMN users.spending_updated_at; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.users.spending_updated_at IS 'Last time spending stats were updated';


--
-- Name: COLUMN users.customer_tier; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.users.customer_tier IS 'Customer tier: basic, advance, ultimate';


--
-- Name: COLUMN users.zodiac; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.users.zodiac IS 'User zodiac sign calculated from birthdate';


--
-- Name: COLUMN users.spending_6_months; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.users.spending_6_months IS 'Total spending in current 6-month period';


--
-- Name: COLUMN users.tier_period_start; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.users.tier_period_start IS 'Start date of current tier evaluation period';


--
-- Name: COLUMN users.last_tier_evaluation; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.users.last_tier_evaluation IS 'Last time tier was evaluated';


--
-- Name: COLUMN users.points_balance; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.users.points_balance IS 'Current available points balance';


--
-- Name: COLUMN users.total_points_earned; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.users.total_points_earned IS 'Total points earned from purchases';


--
-- Name: COLUMN users.total_points_redeemed; Type: COMMENT; Schema: public; Owner: sneaker_user
--

COMMENT ON COLUMN public.users.total_points_redeemed IS 'Total points redeemed';


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO sneaker_user;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: voucher_sync_log; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.voucher_sync_log (
    id uuid NOT NULL,
    sync_type character varying(50) NOT NULL,
    status character varying(20) NOT NULL,
    records_processed integer DEFAULT 0 NOT NULL,
    errors_count integer DEFAULT 0 NOT NULL,
    error_details text,
    synced_at timestamp(0) without time zone NOT NULL,
    execution_time_ms integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.voucher_sync_log OWNER TO sneaker_user;

--
-- Name: voucher_usage; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.voucher_usage (
    id uuid NOT NULL,
    voucher_id uuid NOT NULL,
    customer_id character varying(100) NOT NULL,
    customer_email character varying(255) NOT NULL,
    order_id character varying(100) NOT NULL,
    discount_amount numeric(12,2) NOT NULL,
    order_total numeric(12,2) NOT NULL,
    used_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.voucher_usage OWNER TO sneaker_user;

--
-- Name: vouchers; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.vouchers (
    id uuid NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    sync_status character varying(20) DEFAULT 'synced'::character varying NOT NULL,
    spreadsheet_row_id integer,
    code_product character varying(255) DEFAULT 'All product'::character varying NOT NULL,
    voucher_code character varying(100) NOT NULL,
    name_voucher character varying(255) NOT NULL,
    start_date timestamp(0) without time zone,
    end_date timestamp(0) without time zone,
    min_purchase numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    quota integer DEFAULT 0 NOT NULL,
    claim_per_customer integer DEFAULT 1 NOT NULL,
    voucher_type character varying(255) NOT NULL,
    value character varying(50) NOT NULL,
    discount_max numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    category_customer character varying(100) DEFAULT 'all customer'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    total_used integer DEFAULT 0 NOT NULL,
    remaining_quota integer GENERATED ALWAYS AS ((quota - total_used)) STORED NOT NULL,
    CONSTRAINT vouchers_voucher_type_check CHECK (((voucher_type)::text = ANY ((ARRAY['NOMINAL'::character varying, 'PERCENT'::character varying])::text[])))
);


ALTER TABLE public.vouchers OWNER TO sneaker_user;

--
-- Name: webhook_events; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.webhook_events (
    id bigint NOT NULL,
    event_id character varying(255) NOT NULL,
    source character varying(255) DEFAULT 'ginee'::character varying NOT NULL,
    entity character varying(255),
    action character varying(255),
    payload jsonb,
    processed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    event_type character varying(100),
    ip_address character varying(45),
    user_agent character varying(255),
    headers jsonb,
    processed boolean DEFAULT false NOT NULL,
    processing_result text,
    retry_count integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.webhook_events OWNER TO sneaker_user;

--
-- Name: webhook_events_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.webhook_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.webhook_events_id_seq OWNER TO sneaker_user;

--
-- Name: webhook_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.webhook_events_id_seq OWNED BY public.webhook_events.id;


--
-- Name: wishlists; Type: TABLE; Schema: public; Owner: sneaker_user
--

CREATE TABLE public.wishlists (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    product_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.wishlists OWNER TO sneaker_user;

--
-- Name: wishlists_id_seq; Type: SEQUENCE; Schema: public; Owner: sneaker_user
--

CREATE SEQUENCE public.wishlists_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.wishlists_id_seq OWNER TO sneaker_user;

--
-- Name: wishlists_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: sneaker_user
--

ALTER SEQUENCE public.wishlists_id_seq OWNED BY public.wishlists.id;


--
-- Name: categories id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.categories ALTER COLUMN id SET DEFAULT nextval('public.categories_id_seq'::regclass);


--
-- Name: coupon_usages id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.coupon_usages ALTER COLUMN id SET DEFAULT nextval('public.coupon_usages_id_seq'::regclass);


--
-- Name: coupons id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.coupons ALTER COLUMN id SET DEFAULT nextval('public.coupons_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: ginee_product_mappings id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.ginee_product_mappings ALTER COLUMN id SET DEFAULT nextval('public.ginee_product_mappings_id_seq'::regclass);


--
-- Name: ginee_sync_logs id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.ginee_sync_logs ALTER COLUMN id SET DEFAULT nextval('public.ginee_sync_logs_id_seq'::regclass);


--
-- Name: google_sheets_sync_logs id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.google_sheets_sync_logs ALTER COLUMN id SET DEFAULT nextval('public.google_sheets_sync_logs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: menu_navigation id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.menu_navigation ALTER COLUMN id SET DEFAULT nextval('public.menu_navigation_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: order_items id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.order_items ALTER COLUMN id SET DEFAULT nextval('public.order_items_id_seq'::regclass);


--
-- Name: orders id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.orders ALTER COLUMN id SET DEFAULT nextval('public.orders_id_seq'::regclass);


--
-- Name: points_transactions id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.points_transactions ALTER COLUMN id SET DEFAULT nextval('public.points_transactions_id_seq'::regclass);


--
-- Name: products id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- Name: shopping_cart id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.shopping_cart ALTER COLUMN id SET DEFAULT nextval('public.shopping_cart_id_seq'::regclass);


--
-- Name: user_addresses id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.user_addresses ALTER COLUMN id SET DEFAULT nextval('public.user_addresses_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: webhook_events id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.webhook_events ALTER COLUMN id SET DEFAULT nextval('public.webhook_events_id_seq'::regclass);


--
-- Name: wishlists id; Type: DEFAULT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.wishlists ALTER COLUMN id SET DEFAULT nextval('public.wishlists_id_seq'::regclass);


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.cache (key, value, expiration) FROM stdin;
sneakerflash-cache-356a192b7913b04c54574d18c28d46e6395428ab:timer	i:1754738822;	1754738822
sneakerflash-cache-356a192b7913b04c54574d18c28d46e6395428ab	i:1;	1754738822
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: categories; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.categories (id, name, slug, description, image, is_active, sort_order, menu_placement, secondary_menus, show_in_menu, is_featured, category_keywords, meta_title, meta_description, meta_keywords, brand_color, meta_data, created_at, updated_at) FROM stdin;
1	All Footwear	all-footwear	Complete collection of footwear for all occasions	\N	t	1	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-02 03:54:01	2025-08-02 03:54:01
2	Lifestyle/Casual	lifestyle-casual	Casual sneakers and lifestyle shoes for everyday wear	\N	t	2	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-02 03:54:01	2025-08-02 03:54:01
3	Running	running	High-performance running shoes with advanced cushioning	\N	t	3	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-02 03:54:01	2025-08-02 03:54:01
4	Training	training	Cross-training and gym shoes for fitness enthusiasts	\N	t	4	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-02 03:54:01	2025-08-02 03:54:01
5	Basketball	basketball	Basketball shoes with superior ankle support and grip	\N	t	5	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-02 03:54:01	2025-08-02 03:54:01
6	Basketball Shoes	basketball-shoes	Professional basketball footwear with premium technology	\N	t	6	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-02 03:54:01	2025-08-02 03:54:01
7	Casual Sneakers	casual-sneakers	Comfortable sneakers for daily activities and casual outings	\N	t	7	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-02 03:54:01	2025-08-02 03:54:01
8	Running Shoes	running-shoes	Advanced running footwear with responsive cushioning	\N	t	8	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-02 03:54:01	2025-08-02 03:54:01
10	Accessories	accessories	Sports accessories, bags, and apparel	\N	t	10	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-02 03:54:01	2025-08-02 03:54:01
11	Kids Shoes	kids-shoes	Comfortable and durable shoes designed for children	\N	t	11	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-02 03:54:01	2025-08-02 03:54:01
12	Sneakers	sneakers	\N	\N	t	0	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-06 20:18:18	2025-08-06 20:18:18
13	Apparel	apparel	Apparel products	\N	t	0	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-11 14:50:19	2025-08-11 14:50:19
14	Lifestyle	lifestyle	Lifestyle products	\N	t	0	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-11 14:50:19	2025-08-11 14:50:19
15	Caps & Hats	caps-hats	Caps & Hats products	\N	t	0	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-11 14:50:21	2025-08-11 14:50:21
16	Badminton	badminton	Badminton products	\N	t	0	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-11 14:50:21	2025-08-11 14:50:21
17	Bags	bags	Bags products	\N	t	0	\N	\N	t	f	\N	\N	\N	\N	\N	\N	2025-08-11 14:50:22	2025-08-11 14:50:22
\.


--
-- Data for Name: coupon_usages; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.coupon_usages (id, user_id, coupon_id, order_id, discount_amount, used_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: coupons; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.coupons (id, code, name, description, type, value, minimum_amount, maximum_discount, usage_limit, used_count, is_active, starts_at, expires_at, applicable_categories, applicable_products, created_at, updated_at) FROM stdin;
2	SAVE50K	Fixed Amount Discount	Get Rp 50,000 off your order	fixed_amount	50000.00	250000.00	\N	500	0	t	\N	2025-09-14 15:59:42	\N	\N	2025-08-14 15:59:42	2025-08-14 15:59:42
3	FREESHIP	Free Shipping Promo	Get free shipping on orders above Rp 200,000	free_shipping	0.00	200000.00	\N	200	0	t	\N	2025-08-28 15:59:42	\N	\N	2025-08-14 15:59:42	2025-08-14 15:59:42
4	FLASH25	24 Hour Flash Sale	25% off everything - limited time only!	percentage	25.00	150000.00	500000.00	100	0	t	2025-08-14 15:59:42	2025-08-15 15:59:42	\N	\N	2025-08-14 15:59:42	2025-08-14 15:59:42
5	STUDENT15	Student Discount	15% discount for students	percentage	15.00	75000.00	150000.00	\N	0	t	\N	2025-11-14 15:59:42	\N	\N	2025-08-14 15:59:42	2025-08-14 15:59:42
6	EXPIRED	Expired Coupon (Test)	This coupon has expired - for testing	percentage	20.00	100000.00	\N	50	0	t	\N	2025-08-07 15:59:42	\N	\N	2025-08-14 15:59:42	2025-08-14 15:59:42
7	FUTURE	Future Coupon (Test)	This coupon starts in the future - for testing	fixed_amount	100000.00	300000.00	\N	50	0	t	2025-08-21 15:59:42	2025-09-14 15:59:42	\N	\N	2025-08-14 15:59:42	2025-08-14 15:59:42
8	BIGSPENDER	High Value Customer Discount	Exclusive discount for orders above Rp 1,000,000	percentage	20.00	1000000.00	1000000.00	50	0	t	\N	2025-10-14 15:59:42	\N	\N	2025-08-14 15:59:42	2025-08-14 15:59:42
9	WEEKEND	Weekend Special	Weekend-only discount	percentage	12.00	120000.00	300000.00	300	0	t	\N	2025-09-11 15:59:42	\N	\N	2025-08-14 15:59:42	2025-08-14 15:59:42
10	LOYALCUSTOMER	Loyal Customer Reward	Thank you for being a loyal customer!	fixed_amount	75000.00	200000.00	\N	1000	0	t	\N	2025-12-14 15:59:42	\N	\N	2025-08-14 15:59:42	2025-08-14 15:59:42
1	WELCOME10	Welcome New Customer	Special 10% discount for new customers	percentage	5.00	1000000.00	100000.00	1000	0	t	\N	2026-02-14 15:59:42	[]	[]	2025-08-14 15:59:42	2025-08-14 16:06:55
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: ginee_product_mappings; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.ginee_product_mappings (id, product_id, ginee_master_sku, ginee_product_id, ginee_warehouse_id, sync_enabled, stock_sync_enabled, price_sync_enabled, last_product_sync, last_stock_sync, last_price_sync, stock_quantity_ginee, price_ginee, ginee_product_data, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: ginee_sync_logs; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.ginee_sync_logs (id, type, status, items_processed, items_successful, items_failed, summary, error_message, started_at, completed_at, created_at, updated_at, operation_type, sku, product_name, old_stock, old_warehouse_stock, new_stock, new_warehouse_stock, message, ginee_response, transaction_id, method_used, initiated_by_user, dry_run, batch_size, session_id) FROM stdin;
4	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-18 23:58:12	2025-08-18 23:58:12	sync	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB	0	\N	7	\N	Dry run - would update from 0 to 7	\N	\N	\N	\N	t	\N	session_20250818_235810_n956MK
5	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-18 23:58:13	2025-08-18 23:58:13	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	0	\N	1307	\N	Dry run - would update from 0 to 1307	\N	\N	\N	\N	t	\N	session_20250818_235810_n956MK
6	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 00:02:09	2025-08-19 00:02:09	sync	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB	0	\N	7	\N	Dry run - would update from 0 to 7	\N	\N	\N	\N	t	\N	session_20250819_000208_PADvir
7	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 00:02:11	2025-08-19 00:02:11	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	0	\N	1307	\N	Dry run - would update from 0 to 1307	\N	\N	\N	\N	t	\N	session_20250819_000208_PADvir
8	stock_push	success	0	0	0	\N	\N	\N	\N	2025-08-19 00:02:51	2025-08-19 00:02:51	sync	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB	0	\N	7	\N	Updated from 0 to 7	\N	\N	\N	\N	f	\N	session_20250819_000251_HsEGXW
9	stock_push	success	0	0	0	\N	\N	\N	\N	2025-08-19 00:02:52	2025-08-19 00:02:52	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	0	\N	1307	\N	Updated from 0 to 1307	\N	\N	\N	\N	f	\N	session_20250819_000251_HsEGXW
10	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 00:04:31	2025-08-19 00:04:31	push	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB-44 - Size 20	7	\N	7	\N	Dry run - no actual push performed	\N	\N	\N	\N	t	\N	session_20250819_000431_Rj6WfF
11	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 00:04:31	2025-08-19 00:04:31	push	BOX	Doube Box - Size 30	1300	\N	1300	\N	Dry run - no actual push performed	\N	\N	\N	\N	t	\N	session_20250819_000431_Rj6WfF
12	stock_push	success	0	0	0	\N	\N	\N	\N	2025-08-19 00:04:48	2025-08-19 00:04:48	push	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB-44 - Size 20	7	\N	7	\N	Successfully pushed to Ginee	{"code": "SUCCESS", "data": {"stockList": [{"outStock": 0, "masterSku": "BOX", "spareStock": 0, "lockedStock": 1, "safetyStock": 0, "availableStock": 1299, "promotionStock": 0, "transportStock": 0, "updateDatetime": "2025-08-18T17:04:48Z", "warehouseStock": 1300, "masterProductName": "DOUBLE BOX BY SNEAKERS FLASH", "masterVariationId": "MV642D173846E0FB0001E657B8"}, {"outStock": 0, "masterSku": "197375689975", "spareStock": 0, "lockedStock": 1, "safetyStock": 0, "availableStock": 6, "promotionStock": 0, "transportStock": 0, "updateDatetime": "2025-08-18T17:04:48Z", "warehouseStock": 7, "masterProductName": "Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB-44", "masterVariationId": "MV68182B3F4CEDFD0001772241"}], "warehouseId": "WW614C57B6E21B840001B4A467"}, "extra": null, "message": "OK", "transactionId": "4ec7cd0fb28c6946", "pricingStrategy": "PAY"}	\N	\N	\N	f	\N	session_20250819_000447_uDiMQR
13	stock_push	success	0	0	0	\N	\N	\N	\N	2025-08-19 00:04:48	2025-08-19 00:04:48	push	BOX	Doube Box - Size 30	1300	\N	1300	\N	Successfully pushed to Ginee	{"code": "SUCCESS", "data": {"stockList": [{"outStock": 0, "masterSku": "BOX", "spareStock": 0, "lockedStock": 1, "safetyStock": 0, "availableStock": 1299, "promotionStock": 0, "transportStock": 0, "updateDatetime": "2025-08-18T17:04:48Z", "warehouseStock": 1300, "masterProductName": "DOUBLE BOX BY SNEAKERS FLASH", "masterVariationId": "MV642D173846E0FB0001E657B8"}, {"outStock": 0, "masterSku": "197375689975", "spareStock": 0, "lockedStock": 1, "safetyStock": 0, "availableStock": 6, "promotionStock": 0, "transportStock": 0, "updateDatetime": "2025-08-18T17:04:48Z", "warehouseStock": 7, "masterProductName": "Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB-44", "masterVariationId": "MV68182B3F4CEDFD0001772241"}], "warehouseId": "WW614C57B6E21B840001B4A467"}, "extra": null, "message": "OK", "transactionId": "4ec7cd0fb28c6946", "pricingStrategy": "PAY"}	\N	\N	\N	f	\N	session_20250819_000447_uDiMQR
14	stock_push	success	0	0	0	\N	\N	\N	\N	2025-08-19 00:05:50	2025-08-19 00:05:50	push	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB-44 - Size 20	3	\N	3	\N	Successfully pushed to Ginee	{"code": "SUCCESS", "data": {"stockList": [{"outStock": 0, "masterSku": "BOX", "spareStock": 0, "lockedStock": 1, "safetyStock": 0, "availableStock": 1306, "promotionStock": 0, "transportStock": 0, "updateDatetime": "2025-08-18T17:05:50Z", "warehouseStock": 1307, "masterProductName": "DOUBLE BOX BY SNEAKERS FLASH", "masterVariationId": "MV642D173846E0FB0001E657B8"}, {"outStock": 0, "masterSku": "197375689975", "spareStock": 0, "lockedStock": 1, "safetyStock": 0, "availableStock": 2, "promotionStock": 0, "transportStock": 0, "updateDatetime": "2025-08-18T17:05:50Z", "warehouseStock": 3, "masterProductName": "Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB-44", "masterVariationId": "MV68182B3F4CEDFD0001772241"}], "warehouseId": "WW614C57B6E21B840001B4A467"}, "extra": null, "message": "OK", "transactionId": "0d6d113f7b5cefed", "pricingStrategy": "PAY"}	\N	\N	\N	f	\N	session_20250819_000549_g5y7uf
15	stock_push	success	0	0	0	\N	\N	\N	\N	2025-08-19 00:05:50	2025-08-19 00:05:50	push	BOX	Doube Box - Size 30	1307	\N	1307	\N	Successfully pushed to Ginee	{"code": "SUCCESS", "data": {"stockList": [{"outStock": 0, "masterSku": "BOX", "spareStock": 0, "lockedStock": 1, "safetyStock": 0, "availableStock": 1306, "promotionStock": 0, "transportStock": 0, "updateDatetime": "2025-08-18T17:05:50Z", "warehouseStock": 1307, "masterProductName": "DOUBLE BOX BY SNEAKERS FLASH", "masterVariationId": "MV642D173846E0FB0001E657B8"}, {"outStock": 0, "masterSku": "197375689975", "spareStock": 0, "lockedStock": 1, "safetyStock": 0, "availableStock": 2, "promotionStock": 0, "transportStock": 0, "updateDatetime": "2025-08-18T17:05:50Z", "warehouseStock": 3, "masterProductName": "Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB-44", "masterVariationId": "MV68182B3F4CEDFD0001772241"}], "warehouseId": "WW614C57B6E21B840001B4A467"}, "extra": null, "message": "OK", "transactionId": "0d6d113f7b5cefed", "pricingStrategy": "PAY"}	\N	\N	\N	f	\N	session_20250819_000549_g5y7uf
16	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 00:06:08	2025-08-19 00:06:08	sync	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB	3	\N	7	\N	Dry run - would update from 3 to 7	\N	\N	\N	\N	t	\N	session_20250819_000607_TUV6cS
17	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 00:06:09	2025-08-19 00:06:09	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	1307	\N	1307	\N	Dry run - would update from 1307 to 1307	\N	\N	\N	\N	t	\N	session_20250819_000607_TUV6cS
18	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 00:10:44	2025-08-19 00:10:44	sync	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB	7	\N	7	\N	Dry run - would update from 7 to 7	\N	\N	\N	\N	t	\N	session_20250819_001044_Its0bA
19	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 00:10:45	2025-08-19 00:10:45	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	1300	\N	1307	\N	Dry run - would update from 1300 to 1307	\N	\N	\N	\N	t	\N	session_20250819_001044_Its0bA
20	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 00:12:37	2025-08-19 00:12:37	sync	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB	0	\N	7	\N	Dry run - would update from 0 to 7	\N	\N	\N	\N	t	\N	session_20250819_001236_hGME0J
21	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 00:12:37	2025-08-19 00:12:37	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	0	\N	1307	\N	Dry run - would update from 0 to 1307	\N	\N	\N	\N	t	\N	session_20250819_001236_hGME0J
22	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:33:41	2025-08-19 08:33:41	sync	11B794301/42	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301	0	\N	1	\N	Dry run - would update from 0 to 1	\N	\N	\N	\N	t	\N	session_20250819_083340_LNCRul
23	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:33:43	2025-08-19 08:33:43	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	0	\N	1307	\N	Dry run - would update from 0 to 1307	\N	\N	\N	\N	t	\N	session_20250819_083340_LNCRul
24	stock_push	success	0	0	0	\N	\N	\N	\N	2025-08-19 08:34:35	2025-08-19 08:34:35	sync	11B794301/42	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301	0	\N	1	\N	Updated from 0 to 1	\N	\N	\N	\N	f	\N	session_20250819_083434_fC3Wbk
25	stock_push	success	0	0	0	\N	\N	\N	\N	2025-08-19 08:34:36	2025-08-19 08:34:36	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	0	\N	1307	\N	Updated from 0 to 1307	\N	\N	\N	\N	f	\N	session_20250819_083434_fC3Wbk
26	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:36:37	2025-08-19 08:36:37	sync	11B794301/42	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301	0	\N	1	\N	Dry run - would update from 0 to 1	\N	\N	\N	\N	t	\N	session_20250819_083636_QQ5mJi
27	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:36:38	2025-08-19 08:36:38	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	0	\N	1307	\N	Dry run - would update from 0 to 1307	\N	\N	\N	\N	t	\N	session_20250819_083636_QQ5mJi
28	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:36:38	2025-08-19 08:36:38	sync	MS237MCN/42	Sepatu Sneakers Pria NEW BALANCE 237 GREY - MS237MCN	0	\N	11	\N	Dry run - would update from 0 to 11	\N	\N	\N	\N	t	\N	session_20250819_083636_QQ5mJi
29	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:39:22	2025-08-19 08:39:22	sync	11B794301/42	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301	0	\N	1	\N	Dry run - would update from 0 to 1	\N	\N	\N	\N	t	\N	session_20250819_083922_AUQ0t3
30	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:39:23	2025-08-19 08:39:23	sync	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB	0	\N	7	\N	Dry run - would update from 0 to 7	\N	\N	\N	\N	t	\N	session_20250819_083922_AUQ0t3
31	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:39:24	2025-08-19 08:39:24	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	0	\N	1307	\N	Dry run - would update from 0 to 1307	\N	\N	\N	\N	t	\N	session_20250819_083922_AUQ0t3
32	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:39:25	2025-08-19 08:39:25	sync	MS237MCN/42	Sepatu Sneakers Pria NEW BALANCE 237 GREY - MS237MCN	0	\N	11	\N	Dry run - would update from 0 to 11	\N	\N	\N	\N	t	\N	session_20250819_083922_AUQ0t3
33	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:55:44	2025-08-19 08:55:44	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	\N	\N	\N	\N	SKIPPED: Product BOX has unreliable status 'PENDING_REVIEW' - sync may be inaccurate	{"reason": "unreliable_status", "product_status": "PENDING_REVIEW"}	\N	\N	\N	t	\N	session_20250819_085544_JH8Tmx
34	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:55:46	2025-08-19 08:55:46	sync	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB	\N	\N	\N	\N	SKIPPED: Product 197375689975 has unreliable status 'PENDING_REVIEW' - sync may be inaccurate	{"reason": "unreliable_status", "product_status": "PENDING_REVIEW"}	\N	\N	\N	t	\N	session_20250819_085546_w2Th48
35	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:55:46	2025-08-19 08:55:46	sync	MS237MCN/42	Sepatu Sneakers Pria NEW BALANCE 237 GREY - MS237MCN	\N	\N	\N	\N	SKIPPED: Product MS237MCN/42 has unreliable status 'PENDING_REVIEW' - sync may be inaccurate	{"reason": "unreliable_status", "product_status": "PENDING_REVIEW"}	\N	\N	\N	t	\N	session_20250819_085546_ki5sFZ
36	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:55:47	2025-08-19 08:55:47	sync	11B794301/42	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301	\N	\N	\N	\N	SKIPPED: Product 11B794301/42 has unreliable status 'PENDING_REVIEW' - sync may be inaccurate	{"reason": "unreliable_status", "product_status": "PENDING_REVIEW"}	\N	\N	\N	t	\N	session_20250819_085547_CRR7QI
37	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:56:01	2025-08-19 08:56:01	sync	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB	\N	\N	\N	\N	SKIPPED: Product 197375689975 has unreliable status 'PENDING_REVIEW' - sync may be inaccurate	{"reason": "unreliable_status", "product_status": "PENDING_REVIEW"}	\N	\N	\N	t	\N	session_20250819_085601_xNJsm1
38	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:57:57	2025-08-19 08:57:57	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	\N	\N	\N	\N	SKIPPED: Product BOX has unreliable status 'PENDING_REVIEW' - sync may be inaccurate	{"reason": "unreliable_status", "product_status": "PENDING_REVIEW"}	\N	\N	\N	t	\N	session_20250819_085757_g5KdLq
39	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:58:10	2025-08-19 08:58:10	sync	11B794301/42	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301	\N	\N	\N	\N	SKIPPED: Product 11B794301/42 has unreliable status 'PENDING_REVIEW' - sync may be inaccurate	{"reason": "unreliable_status", "product_status": "PENDING_REVIEW"}	\N	\N	\N	t	\N	session_20250819_085810_C5aElQ
40	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:58:11	2025-08-19 08:58:11	sync	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB	\N	\N	\N	\N	SKIPPED: Product 197375689975 has unreliable status 'PENDING_REVIEW' - sync may be inaccurate	{"reason": "unreliable_status", "product_status": "PENDING_REVIEW"}	\N	\N	\N	t	\N	session_20250819_085811_LzyfRC
41	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:58:12	2025-08-19 08:58:12	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	\N	\N	\N	\N	SKIPPED: Product BOX has unreliable status 'PENDING_REVIEW' - sync may be inaccurate	{"reason": "unreliable_status", "product_status": "PENDING_REVIEW"}	\N	\N	\N	t	\N	session_20250819_085812_IeIyDb
42	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 08:58:13	2025-08-19 08:58:13	sync	MS237MCN/42	Sepatu Sneakers Pria NEW BALANCE 237 GREY - MS237MCN	\N	\N	\N	\N	SKIPPED: Product MS237MCN/42 has unreliable status 'PENDING_REVIEW' - sync may be inaccurate	{"reason": "unreliable_status", "product_status": "PENDING_REVIEW"}	\N	\N	\N	t	\N	session_20250819_085813_HyqgLg
44	manual	success	0	0	0	\N	\N	\N	\N	2025-08-19 09:55:06	2025-08-19 09:55:06	dashboard_sync	BOX	Doube Box - Size 30	0	\N	1308	\N	Dashboard sync - correcting API inconsistency	{"reason": "api_dashboard_inconsistency", "api_value": 0, "dashboard_value": 1308}	\N	\N	\N	f	\N	dashboard_truth_095506
45	manual	success	0	0	0	\N	\N	\N	\N	2025-08-19 09:55:06	2025-08-19 09:55:06	dashboard_sync	MS237MCN/42	Sepatu Sneakers Pria NEW BALANCE 237 GREY - MS237MCN-42	0	\N	11	\N	Dashboard sync - correcting API inconsistency	{"reason": "api_dashboard_inconsistency", "api_value": 0, "dashboard_value": 11}	\N	\N	\N	f	\N	dashboard_truth_095506
46	manual	success	0	0	0	\N	\N	\N	\N	2025-08-19 09:55:06	2025-08-19 09:55:06	dashboard_sync	11B794301/42	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301-42 - Size 20	0	\N	1	\N	Dashboard sync - correcting API inconsistency	{"reason": "api_dashboard_inconsistency", "api_value": 0, "dashboard_value": 1}	\N	\N	\N	f	\N	dashboard_truth_095506
47	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 10:23:27	2025-08-19 10:23:27	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	0	\N	1307	\N	Dry run - would update from 0 to 1307	\N	\N	\N	\N	t	\N	session_20250819_102325_agj9kj
48	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 10:23:29	2025-08-19 10:23:29	sync	11B794301/42	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301	0	\N	1	\N	Dry run - would update from 0 to 1	\N	\N	\N	\N	t	\N	session_20250819_102325_agj9kj
49	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 10:23:30	2025-08-19 10:23:30	sync	MS237MCN/42	Sepatu Sneakers Pria NEW BALANCE 237 GREY - MS237MCN	0	\N	11	\N	Dry run - would update from 0 to 11	\N	\N	\N	\N	t	\N	session_20250819_102325_agj9kj
50	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 10:23:30	2025-08-19 10:23:30	sync	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB	0	\N	7	\N	Dry run - would update from 0 to 7	\N	\N	\N	\N	t	\N	session_20250819_102325_agj9kj
51	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 10:27:06	2025-08-19 10:27:06	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	0	\N	1307	\N	Dry run - would update from 0 to 1307	\N	\N	\N	\N	t	\N	session_20250819_102705_zp0Ylg
52	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 10:27:07	2025-08-19 10:27:07	sync	11B794301/42	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301	0	\N	1	\N	Dry run - would update from 0 to 1	\N	\N	\N	\N	t	\N	session_20250819_102705_zp0Ylg
53	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 10:27:08	2025-08-19 10:27:08	sync	MS237MCN/42	Sepatu Sneakers Pria NEW BALANCE 237 GREY - MS237MCN	0	\N	11	\N	Dry run - would update from 0 to 11	\N	\N	\N	\N	t	\N	session_20250819_102705_zp0Ylg
54	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 10:27:09	2025-08-19 10:27:09	sync	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB	0	\N	7	\N	Dry run - would update from 0 to 7	\N	\N	\N	\N	t	\N	session_20250819_102705_zp0Ylg
55	smart_sync_batch	preview	0	0	0	\N	\N	\N	\N	2025-08-19 10:27:34	2025-08-19 10:27:34	smart_sync_batch	BOX	Doube Box - Size 30	0	\N	1307	\N	Preview: would update 0 → 1307 via master_products	\N	\N	master_products	\N	t	\N	smart_sync_batch_102732
56	smart_sync_batch	preview	0	0	0	\N	\N	\N	\N	2025-08-19 10:27:35	2025-08-19 10:27:35	smart_sync_batch	11B794301/42	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301-42 - Size 20	0	\N	1	\N	Preview: would update 0 → 1 via master_products	\N	\N	master_products	\N	t	\N	smart_sync_batch_102732
57	smart_sync_batch	preview	0	0	0	\N	\N	\N	\N	2025-08-19 10:27:37	2025-08-19 10:27:37	smart_sync_batch	MS237MCN/42	Sepatu Sneakers Pria NEW BALANCE 237 GREY - MS237MCN-42	0	\N	11	\N	Preview: would update 0 → 11 via master_products	\N	\N	master_products	\N	t	\N	smart_sync_batch_102732
58	smart_sync_batch	preview	0	0	0	\N	\N	\N	\N	2025-08-19 10:27:38	2025-08-19 10:27:38	smart_sync_batch	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB-44 - Size 43	0	\N	7	\N	Preview: would update 0 → 7 via master_products	\N	\N	master_products	\N	t	\N	smart_sync_batch_102732
59	smart_sync_batch	preview	0	0	0	\N	\N	\N	\N	2025-08-19 10:28:10	2025-08-19 10:28:10	smart_sync_batch	BOX	Doube Box - Size 30	0	\N	1307	\N	Preview: would update 0 → 1307 via master_products	\N	\N	master_products	\N	t	\N	smart_sync_batch_102809
60	smart_sync_batch	preview	0	0	0	\N	\N	\N	\N	2025-08-19 10:28:11	2025-08-19 10:28:11	smart_sync_batch	11B794301/42	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301-42 - Size 20	0	\N	1	\N	Preview: would update 0 → 1 via master_products	\N	\N	master_products	\N	t	\N	smart_sync_batch_102809
61	smart_sync_batch	preview	0	0	0	\N	\N	\N	\N	2025-08-19 10:28:13	2025-08-19 10:28:13	smart_sync_batch	MS237MCN/42	Sepatu Sneakers Pria NEW BALANCE 237 GREY - MS237MCN-42	0	\N	11	\N	Preview: would update 0 → 11 via master_products	\N	\N	master_products	\N	t	\N	smart_sync_batch_102809
62	smart_sync_batch	preview	0	0	0	\N	\N	\N	\N	2025-08-19 10:28:14	2025-08-19 10:28:14	smart_sync_batch	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB-44 - Size 43	0	\N	7	\N	Preview: would update 0 → 7 via master_products	\N	\N	master_products	\N	t	\N	smart_sync_batch_102809
63	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 11:50:50	2025-08-19 11:50:50	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	0	\N	1307	\N	Dry run - would update from 0 to 1307	\N	\N	\N	\N	t	\N	session_20250819_115047_cGiNo2
64	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 11:50:53	2025-08-19 11:50:53	sync	11B794301/42	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301	0	\N	1	\N	Dry run - would update from 0 to 1	\N	\N	\N	\N	t	\N	session_20250819_115047_cGiNo2
65	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 11:50:54	2025-08-19 11:50:54	sync	MS237MCN/42	Sepatu Sneakers Pria NEW BALANCE 237 GREY - MS237MCN	0	\N	11	\N	Dry run - would update from 0 to 11	\N	\N	\N	\N	t	\N	session_20250819_115047_cGiNo2
66	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 11:50:55	2025-08-19 11:50:55	sync	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB	0	\N	7	\N	Dry run - would update from 0 to 7	\N	\N	\N	\N	t	\N	session_20250819_115047_cGiNo2
67	stock_push	success	0	0	0	\N	\N	\N	\N	2025-08-19 11:51:23	2025-08-19 11:51:23	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	0	\N	1307	\N	Updated from 0 to 1307	\N	\N	\N	\N	f	\N	session_20250819_115122_aaD7Q5
68	stock_push	success	0	0	0	\N	\N	\N	\N	2025-08-19 11:51:24	2025-08-19 11:51:24	sync	11B794301/42	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301	0	\N	1	\N	Updated from 0 to 1	\N	\N	\N	\N	f	\N	session_20250819_115122_aaD7Q5
69	stock_push	success	0	0	0	\N	\N	\N	\N	2025-08-19 11:51:25	2025-08-19 11:51:25	sync	MS237MCN/42	Sepatu Sneakers Pria NEW BALANCE 237 GREY - MS237MCN	0	\N	11	\N	Updated from 0 to 11	\N	\N	\N	\N	f	\N	session_20250819_115122_aaD7Q5
70	stock_push	success	0	0	0	\N	\N	\N	\N	2025-08-19 11:51:26	2025-08-19 11:51:26	sync	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB	0	\N	7	\N	Updated from 0 to 7	\N	\N	\N	\N	f	\N	session_20250819_115122_aaD7Q5
71	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 13:27:56	2025-08-19 13:27:56	sync	BOX	DOUBLE BOX BY SNEAKERS FLASH	1307	\N	1307	\N	Dry run - would update from 1307 to 1307	\N	\N	\N	\N	t	\N	session_20250819_132754_80t3QF
72	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 13:27:57	2025-08-19 13:27:57	sync	11B794301/42	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301	1	\N	1	\N	Dry run - would update from 1 to 1	\N	\N	\N	\N	t	\N	session_20250819_132754_80t3QF
73	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 13:27:58	2025-08-19 13:27:58	sync	MS237MCN/42	Sepatu Sneakers Pria NEW BALANCE 237 GREY - MS237MCN	11	\N	11	\N	Dry run - would update from 11 to 11	\N	\N	\N	\N	t	\N	session_20250819_132754_80t3QF
74	stock_push	skipped	0	0	0	\N	\N	\N	\N	2025-08-19 13:27:59	2025-08-19 13:27:59	sync	197375689975	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB	7	\N	7	\N	Dry run - would update from 7 to 7	\N	\N	\N	\N	t	\N	session_20250819_132754_80t3QF
\.


--
-- Data for Name: google_sheets_sync_logs; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.google_sheets_sync_logs (id, sync_id, spreadsheet_id, sheet_name, initiated_by, started_at, completed_at, status, total_rows, processed_rows, created_products, updated_products, deleted_products, skipped_rows, error_count, unique_sku_parents, unique_skus, products_with_variants, sync_results, error_details, sync_options, sku_mapping, duration_seconds, summary, error_message, sync_strategy, clean_old_data, created_at, updated_at) FROM stdin;
1	sync_ds9GYj9tYjrI_20250811_145018	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-11 14:50:18	2025-08-11 14:50:22	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":65,"products_updated":0,"products_deleted":0,"final_product_count":68}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	3	Safe mode: Created 65, Updated 0, No deletions	\N	safe_mode_no_delete	f	2025-08-11 14:50:18	2025-08-11 14:50:22
2	sync_Lz1ykwtiEEyK_20250811_155454	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-11 15:54:54	2025-08-11 15:54:57	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":2,"products_updated":63,"products_deleted":0,"final_product_count":68}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	3	Safe mode: Created 2, Updated 63, No deletions	\N	safe_mode_no_delete	f	2025-08-11 15:54:54	2025-08-11 15:54:57
3	sync_4UPEtkTMW0E2_20250811_155717	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-11 15:57:17	2025-08-11 15:57:20	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":0,"products_updated":65,"products_deleted":0,"final_product_count":68}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	3	Safe mode: Created 0, Updated 65, No deletions	\N	safe_mode_no_delete	f	2025-08-11 15:57:17	2025-08-11 15:57:20
4	sync_a9UcDRXxjVUL_20250811_234612	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-11 23:46:12	2025-08-11 23:46:15	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":0,"products_updated":65,"products_deleted":0,"final_product_count":68}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	3	Safe mode: Created 0, Updated 65, No deletions	\N	safe_mode_no_delete	f	2025-08-11 23:46:12	2025-08-11 23:46:15
5	sync_5gyE3aJ3AT0Y_20250812_083501	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-12 08:35:01	2025-08-12 08:35:05	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":0,"products_updated":65,"products_deleted":0,"final_product_count":68}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	4	Safe mode: Created 0, Updated 65, No deletions	\N	safe_mode_no_delete	f	2025-08-12 08:35:01	2025-08-12 08:35:05
6	sync_N7qxMPM10azr_20250812_083559	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-12 08:35:59	2025-08-12 08:36:02	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":0,"products_updated":65,"products_deleted":0,"final_product_count":68}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	3	Safe mode: Created 0, Updated 65, No deletions	\N	safe_mode_no_delete	f	2025-08-12 08:35:59	2025-08-12 08:36:02
7	sync_0C1m0LsNfZfT_20250813_142949	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-13 14:29:49	2025-08-13 14:29:52	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":0,"products_updated":65,"products_deleted":0,"final_product_count":68}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	3	Safe mode: Created 0, Updated 65, No deletions	\N	safe_mode_no_delete	f	2025-08-13 14:29:49	2025-08-13 14:29:52
8	sync_PE5TcWTAktKA_20250813_143213	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-13 14:32:13	2025-08-13 14:32:16	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":0,"products_updated":65,"products_deleted":0,"final_product_count":68}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	3	Safe mode: Created 0, Updated 65, No deletions	\N	safe_mode_no_delete	f	2025-08-13 14:32:13	2025-08-13 14:32:16
9	sync_FCxQgcxt9yce_20250813_143310	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-13 14:33:10	2025-08-13 14:33:13	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":0,"products_updated":65,"products_deleted":0,"final_product_count":68}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	3	Safe mode: Created 0, Updated 65, No deletions	\N	safe_mode_no_delete	f	2025-08-13 14:33:10	2025-08-13 14:33:13
10	sync_s7Ht8aHGkKax_20250817_151901	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-17 15:19:01	2025-08-17 15:19:04	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":1,"products_updated":65,"products_deleted":0,"final_product_count":69}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	3	Safe mode: Created 1, Updated 65, No deletions	\N	safe_mode_no_delete	f	2025-08-17 15:19:01	2025-08-17 15:19:04
11	sync_tzJp5EgDFaO1_20250817_151947	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-17 15:19:47	2025-08-17 15:19:51	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":0,"products_updated":66,"products_deleted":0,"final_product_count":69}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	3	Safe mode: Created 0, Updated 66, No deletions	\N	safe_mode_no_delete	f	2025-08-17 15:19:47	2025-08-17 15:19:51
12	sync_TaO0m51GBGTc_20250818_210724	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-18 21:07:24	2025-08-18 21:07:25	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":0,"products_updated":1,"products_deleted":0,"final_product_count":69}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	1	Safe mode: Created 0, Updated 1, No deletions	\N	safe_mode_no_delete	f	2025-08-18 21:07:24	2025-08-18 21:07:25
13	sync_0RHpyfh0Ya2D_20250818_210732	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-18 21:07:32	2025-08-18 21:07:32	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"smart_individual_sku","sku_parents_processed":1,"individual_products_created":1,"old_products_deleted":68,"final_product_count":1}	\N	{"sync_strategy":"smart_individual_sku","notes":null}	\N	1	Updated 1 products, Deleted 68 old products	\N	smart_individual_sku	f	2025-08-18 21:07:32	2025-08-18 21:07:32
14	sync_3gOP1yiCe81u_20250818_212254	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-18 21:22:54	2025-08-18 21:22:54	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":1,"products_updated":1,"products_deleted":0,"final_product_count":2}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	1	Safe mode: Created 1, Updated 1, No deletions	\N	safe_mode_no_delete	f	2025-08-18 21:22:54	2025-08-18 21:22:54
15	sync_zna1arMWojZU_20250818_234057	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-18 23:40:57	2025-08-18 23:40:58	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":0,"products_updated":1,"products_deleted":0,"final_product_count":2}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	1	Safe mode: Created 0, Updated 1, No deletions	\N	safe_mode_no_delete	f	2025-08-18 23:40:57	2025-08-18 23:40:58
16	sync_w8PUBJ7zBgP3_20250818_234122	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-18 23:41:22	2025-08-18 23:41:23	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"smart_individual_sku","sku_parents_processed":1,"individual_products_created":1,"old_products_deleted":1,"final_product_count":1}	\N	{"sync_strategy":"smart_individual_sku"}	\N	1	Updated 1 products, Deleted 1 old products	\N	smart_individual_sku	f	2025-08-18 23:41:22	2025-08-18 23:41:23
17	sync_VHHISnXAT0nw_20250818_234133	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-18 23:41:33	2025-08-18 23:41:34	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":1,"products_updated":1,"products_deleted":0,"final_product_count":2}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	1	Safe mode: Created 1, Updated 1, No deletions	\N	safe_mode_no_delete	f	2025-08-18 23:41:33	2025-08-18 23:41:34
18	sync_yTxMcKAHPwPk_20250819_001228	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-19 00:12:28	2025-08-19 00:12:29	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":2,"products_updated":0,"products_deleted":0,"final_product_count":2}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	1	Safe mode: Created 2, Updated 0, No deletions	\N	safe_mode_no_delete	f	2025-08-19 00:12:28	2025-08-19 00:12:29
19	sync_mnSVnCvE8zJb_20250819_083250	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-19 08:32:50	2025-08-19 08:32:51	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"smart_individual_sku","sku_parents_processed":2,"individual_products_created":2,"old_products_deleted":1,"final_product_count":2}	\N	{"sync_strategy":"smart_individual_sku"}	\N	1	Created 1 products, Updated 1 products, Deleted 1 old products	\N	smart_individual_sku	f	2025-08-19 08:32:50	2025-08-19 08:32:51
20	sync_wj99KWhPVWrB_20250819_083614	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-19 08:36:14	2025-08-19 08:36:15	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":1,"products_updated":2,"products_deleted":0,"final_product_count":3}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	1	Safe mode: Created 1, Updated 2, No deletions	\N	safe_mode_no_delete	f	2025-08-19 08:36:14	2025-08-19 08:36:15
21	sync_eNJPN2x3OuYH_20250819_083906	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-19 08:39:06	2025-08-19 08:39:07	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":1,"products_updated":3,"products_deleted":0,"final_product_count":4}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	1	Safe mode: Created 1, Updated 3, No deletions	\N	safe_mode_no_delete	f	2025-08-19 08:39:06	2025-08-19 08:39:07
22	sync_S723okdxFRTs_20250819_102206	1TJi2-UpmvtnjXWfGG7htw-fLKW-eRI8ERzuJsR-5kcg	Sheet1	1	2025-08-19 10:22:06	2025-08-19 10:22:07	completed	0	0	0	0	0	0	0	0	0	0	{"sync_strategy":"safe_mode_no_delete","products_created":0,"products_updated":4,"products_deleted":0,"final_product_count":4}	\N	{"sync_strategy":"safe_mode_no_delete"}	\N	1	Safe mode: Created 0, Updated 4, No deletions	\N	safe_mode_no_delete	f	2025-08-19 10:22:06	2025-08-19 10:22:07
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: menu_navigation; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.menu_navigation (id, name, slug, url, route_name, icon, description, sort_order, is_active, is_external, target, parent_id, permissions, meta_data, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	2025_07_31_151132_create_categories_table	1
2	2025_07_31_151132_create_orders_table	1
3	2025_07_31_151132_create_products_table	1
4	2025_07_31_151132_create_supporting_tables	1
5	2025_07_31_151133_create_cache_table	1
6	2025_07_31_151133_create_users_table	1
7	2025_07_31_151137_create_jobs_table	1
8	2025_08_02_043456_add_google_fields_to_users_table	2
9	2025_08_02_093453_add_destination_fields_to_orders_table	3
10	2025_08_02_171109_add_weight_to_products_table	4
11	2025_08_03_042555_update_orders_table_for_midtrans	4
12	2025_08_03_155807_add_shipping_columns_to_orders_table	4
13	2025_08_06_100428_create_menu_navigation_table	5
14	2025_08_07_131154_add_spending_stats_to_users_table	6
15	2025_08_07_134739_add_customer_tier_to_users_table	6
17	2025_08_07_102648_add_checkout_fields_to_users_table	7
18	2025_08_08_112550_create_user_addresses_table	8
19	2025_08_10_003709_create_google_sheets_sync_logs_table	9
20	2025_08_10_131228_add_excel_columns_to_products_table	10
21	2025_08_11_235726_fix_gender_target_index_in_products_table	11
22	2025_08_14_094432_create_coupon_usages_table	12
23	2025_08_14_094503_add_coupon_fields_to_orders_table	12
24	2025_08_14_194105_create_vouchers_table	13
25	2025_08_14_194150_create_vouchers_usage_table	13
26	2025_08_14_194226_create_voucher_sync_log_table	14
27	2025_08_15_165257_add_zodiac_to_users_table	15
28	2025_08_16_133236_update_customer_tier_system_to_basic_advance_ultimate	16
29	2025_08_16_145805_create_points_transactions_table	16
30	2025_08_16_193613_add_points_to_orders_table	16
31	2025_08_18_140749_create_webhook_events_table	17
32	2025_08_18_162102_add_ginee_fields_to_products_table	18
33	2025_08_18_162217_add_ginee_fields_to_products_table	18
34	2025_08_18_163909_add_ginee_sync_fields_to_products	19
35	2025_08_18_163953_update_webhook_events_for_ginee	20
36	2025_08_18_164027_create_ginee_sync_logs_table	21
37	2025_08_18_164059_create_ginee_product_mappings_table	22
38	2025_08_18_205833_add_ginee_stock_fields_to_products_table	23
39	2025_08_18_211158_add_ginee_push_timestamp_to_products_table	24
40	2025_08_18_234937_add_missing_columns_to_ginee_sync_logs_table	25
41	2025_08_18_235435_make_ginee_sync_logs_type_nullable	26
42	2025_08_18_235541_update_ginee_sync_logs_status_enum	27
43	2025_08_18_235751_remove_status_constraint_ginee_sync_logs	28
44	2025_08_19_095414_remove_type_constraint_ginee_sync_logs	29
45	2025_08_19_110441_fix_ginee_sync_logs_constraints	30
\.


--
-- Data for Name: order_items; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.order_items (id, order_id, product_id, product_name, product_sku, product_price, quantity, total_price, product_snapshot, created_at, updated_at) FROM stdin;
1	1	1	Adidas Samba Black		1300000.00	1	1300000.00	\N	2025-08-04 03:52:02	2025-08-04 03:52:02
2	2	1	Adidas Samba Black		1300000.00	1	1300000.00	\N	2025-08-04 10:34:39	2025-08-04 10:34:39
3	3	1	Adidas Samba Black		1300000.00	1	1300000.00	\N	2025-08-04 15:01:29	2025-08-04 15:01:29
4	4	1	Adidas Samba Black		1300000.00	1	1300000.00	\N	2025-08-04 15:06:53	2025-08-04 15:06:53
5	5	2	Nike Air Force 1		1000.00	1	1000.00	\N	2025-08-05 02:00:24	2025-08-05 02:00:24
6	6	2	Nike Air Force 1		1000.00	1	1000.00	\N	2025-08-05 02:04:50	2025-08-05 02:04:50
7	7	1	Adidas Samba Black		1300000.00	1	1300000.00	\N	2025-08-05 02:27:57	2025-08-05 02:27:57
8	8	2	Nike Air Force 1		1000.00	4	4000.00	\N	2025-08-05 02:34:30	2025-08-05 02:34:30
9	9	2	Nike Air Force 1		1000.00	1	1000.00	\N	2025-08-05 04:49:44	2025-08-05 04:49:44
10	10	2	Nike Air Force 1		1000.00	1	1000.00	\N	2025-08-05 08:12:45	2025-08-05 08:12:45
11	11	2	Nike Air Force 1		1000.00	1	1000.00	\N	2025-08-05 08:39:57	2025-08-05 08:39:57
12	12	2	Nike Air Force 1		1000.00	2	2000.00	\N	2025-08-05 09:44:33	2025-08-05 09:44:33
13	13	2	Nike Air Force 1		1000.00	2	2000.00	\N	2025-08-05 14:23:30	2025-08-05 14:23:30
14	14	2	Nike Air Force 1		1000.00	1	1000.00	\N	2025-08-05 21:44:23	2025-08-05 21:44:23
15	15	2	Nike Air Force 1		1000.00	1	1000.00	\N	2025-08-05 21:58:14	2025-08-05 21:58:14
16	16	2	Nike Air Force 1		1000.00	1	1000.00	\N	2025-08-05 22:08:56	2025-08-05 22:08:56
17	17	2	Nike Air Force 1		1000.00	2	2000.00	\N	2025-08-06 09:09:47	2025-08-06 09:09:47
18	18	2	Nike Air Force 1		1000.00	1	1000.00	\N	2025-08-07 22:37:08	2025-08-07 22:37:08
19	19	5	Nike Dunk Black	NIKE-AIR005	30000.00	1	30000.00	\N	2025-08-09 17:39:58	2025-08-09 17:39:58
20	20	1	Adidas Samba Black		1300000.00	1	1300000.00	\N	2025-08-09 17:52:04	2025-08-09 17:52:04
21	21	2	Nike Air Force 1		1000.00	1	1000.00	\N	2025-08-10 22:05:34	2025-08-10 22:05:34
22	22	63	ADIDAS Trae Young 3 Low Grey Green Sepatu Basket Pria - IE2703 - Size 44.7	4067886557314	1299000.00	1	1299000.00	\N	2025-08-12 13:50:07	2025-08-12 13:50:07
23	23	9	VANS HOLDER ST CLASSIC CARDINAL Baju Lengan Pendek Unisex - VN0A3HZFCAR	SBKVN0A3HZFCAR-S	649000.00	1	649000.00	\N	2025-08-13 19:45:53	2025-08-13 19:45:53
24	24	63	ADIDAS Trae Young 3 Low Grey Green Sepatu Basket Pria - IE2703 - Size 44.7	4067886557314	1299000.00	1	1299000.00	\N	2025-08-14 00:13:59	2025-08-14 00:13:59
25	25	24	NIKE DUNK HI 1985 BLUE DENIM Sepatu Sneakers Unisex - DQ8799101 - Size 44.5	SBKDQ8799101-445	2999000.00	1	2999000.00	\N	2025-08-14 16:07:42	2025-08-14 16:07:42
26	26	74	NIKE Badminton - DEFG123 - Size 45	DEFG123/45	2500000.00	1	2500000.00	\N	2025-08-15 14:09:42	2025-08-15 14:09:42
27	27	70	NIKE Badminton - ABCDE123 - Size 41	ABCDE123/41	2500000.00	1	2500000.00	\N	2025-08-15 15:02:43	2025-08-15 15:02:43
28	28	11	NIKE DUNK LOW PRM MF NEUTRAL OLIVE Sepatu Sneakers Wanita - DV7415200 - Size 36.5	SBKDV7415200-365	2799000.00	1	2799000.00	\N	2025-08-15 15:29:52	2025-08-15 15:29:52
29	29	11	NIKE DUNK LOW PRM MF NEUTRAL OLIVE Sepatu Sneakers Wanita - DV7415200 - Size 36.5	SBKDV7415200-365	2799000.00	1	2799000.00	\N	2025-08-15 21:09:07	2025-08-15 21:09:07
30	30	21	NIKE DUNK HI 1985 BLUE DENIM Sepatu Sneakers Unisex - DQ8799101 - Size 42.5	SBKDQ8799101-425	2999000.00	1	2999000.00	\N	2025-08-15 22:12:51	2025-08-15 22:12:51
31	31	67	ADIDAS Dame Certified 2.0 Black Cyan Sepatu Basket Pria - IE7792 - Size 42	4067886760967	1049000.00	1	1049000.00	\N	2025-08-17 14:46:03	2025-08-17 14:46:03
32	32	30	NIKE AIR FORCE 1 MID 07 TRIPLE WHITE Sepatu Sneakers Pria - CW2289111 - Size 42.5	SBKCW2289111-425	2799000.00	1	2799000.00	\N	2025-08-18 11:37:51	2025-08-18 11:37:51
\.


--
-- Data for Name: orders; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.orders (id, order_number, user_id, customer_name, customer_email, customer_phone, status, subtotal, tax_amount, shipping_cost, discount_amount, total_amount, currency, shipping_address, billing_address, store_origin, payment_method, payment_status, payment_token, payment_url, tracking_number, shipped_at, delivered_at, notes, meta_data, created_at, updated_at, shipping_destination_id, shipping_destination_label, shipping_postal_code, snap_token, payment_response, coupon_id, coupon_code, points_used, points_discount) FROM stdin;
2	SF-20250804-9ZEF3C	2	Ivan Adhi	ivan@gmail.com	081287809468	pending	1300000.00	143000.00	10800.00	0.00	1453800.00	IDR	"Jalan Ibnu armah"	"Jalan Ibnu armah"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"26024\\",\\"label\\":\\"PANGKALAN JATI, CINERE, DEPOK, JAWA BARAT, 16513\\",\\"postal_code\\":\\"16513\\",\\"full_address\\":\\"Jalan Ibnu armah, PANGKALAN JATI, CINERE, DEPOK, JAWA BARAT, 16513, 16513\\"},\\"customer_info\\":{\\"social_title\\":null,\\"first_name\\":\\"Ivan\\",\\"last_name\\":\\"Adhi\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":false},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-04T10:34:39.569917Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":300}}"	2025-08-04 10:34:39	2025-08-04 10:34:39	26024	PANGKALAN JATI, CINERE, DEPOK, JAWA BARAT, 16513	16513	b3bfa20e-9145-4d86-a3bd-d8736a08a9de	\N	\N	\N	0	0.00
1	SF-20250804-7HK1HG	2	Idam Palada	idampalada08@gmail.com	081287809468	cancelled	1300000.00	143000.00	9000.00	0.00	1452000.00	IDR	"Jl Bank exim no 37"	"Jl Bank exim no 37"	"Jakarta"	credit_card	cancelled	\N	\N	\N	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17549\\",\\"label\\":\\"KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240\\",\\"postal_code\\":\\"12240\\",\\"full_address\\":\\"Jl Bank exim no 37, KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240, 12240\\"},\\"customer_info\\":{\\"social_title\\":null,\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":false},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-04T03:52:02.923278Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":300}}"	2025-08-04 03:52:02	2025-08-04 10:42:23	17549	KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240	12240	9ea30e6f-279c-4f44-ac4b-41ae58b361b7	\N	\N	\N	0	0.00
4	SF-20250804-XHOL7Q	3	Idam Palada	idampalada08@gmail.com	081287809468	delivered	1300000.00	143000.00	9000.00	0.00	1452000.00	IDR	"jalan bank exim no 37"	"jalan bank exim no 37"	"Jakarta"	credit_card	pending	\N	\N	JOSIADJOISAD982902	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17549\\",\\"label\\":\\"KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240\\",\\"postal_code\\":\\"12240\\",\\"full_address\\":\\"jalan bank exim no 37, KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240, 12240\\"},\\"customer_info\\":{\\"social_title\\":null,\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":false},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-04T15:06:53.649088Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":300}}"	2025-08-04 15:06:53	2025-08-04 15:36:55	17549	KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240	12240	4aecd56b-0190-4977-84c4-2cc837bb39a1	\N	\N	\N	0	0.00
3	SF-20250804-HL0TPB	3	Idam Palada	idampalada08@gmail.com	081287809468	cancelled	1300000.00	143000.00	10000.00	0.00	1453000.00	IDR	"Jl bank exim no 37 rt 5"	"Jl bank exim no 37 rt 5"	"Jakarta"	credit_card	cancelled	\N	\N	\N	\N	\N	Shipping: JNE REG - Layanan Reguler	"{\\"shipping_method\\":\\"JNE REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"JNE REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17551\\",\\"label\\":\\"PONDOK PINANG, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12310\\",\\"postal_code\\":\\"12310\\",\\"full_address\\":\\"Jl bank exim no 37 rt 5, PONDOK PINANG, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12310, 12310\\"},\\"customer_info\\":{\\"social_title\\":null,\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":false},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-04T15:01:29.579536Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":300}}"	2025-08-04 15:01:29	2025-08-04 15:12:50	17551	PONDOK PINANG, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12310	12310	ebf40d81-7156-4b60-83b6-f0666559722c	\N	\N	\N	0	0.00
5	SF-20250805-ZUVCID	2	Idam Palada	idampalada08@gmail.com	081287809468	pending	1000.00	110.00	9000.00	0.00	10110.00	IDR	"jalan bank exim no 37"	"jalan bank exim no 37"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17549\\",\\"label\\":\\"KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240\\",\\"postal_code\\":\\"12240\\",\\"full_address\\":\\"jalan bank exim no 37, KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240, 12240\\"},\\"customer_info\\":{\\"social_title\\":null,\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":false},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-05T02:00:24.102411Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":0.5}}"	2025-08-05 02:00:24	2025-08-05 02:00:24	17549	KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240	12240	\N	\N	\N	\N	0	0.00
8	SF-20250805-1TXFMA	5	jingga aulia	jinggaaulia330@gmail.com	0857092627138	pending	4000.00	440.00	9000.00	0.00	13440.00	IDR	"jalan bank exim"	"jalan bank exim"	"Jakarta"	credit_card	processing	\N	\N	\N	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17549\\",\\"label\\":\\"KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240\\",\\"postal_code\\":\\"12240\\",\\"full_address\\":\\"jalan bank exim, KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240, 12240\\"},\\"customer_info\\":{\\"social_title\\":\\"Ms.\\",\\"first_name\\":\\"jingga\\",\\"last_name\\":\\"aulia\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":\\"1\\"},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-05T02:34:30.752098Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":2}}"	2025-08-05 02:34:30	2025-08-05 02:37:11	17549	KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240	12240	1534bd56-6ff0-47d1-851b-82920ba9de1b	\N	\N	\N	0	0.00
7	SF-20250805-VO1XFN	5	jingga aulia	jinggaaulia330@gmail.com	0857092627138	cancelled	1300000.00	143000.00	9000.00	0.00	1452000.00	IDR	"jalan bank exim"	"jalan bank exim"	"Jakarta"	credit_card	cancelled	\N	\N	\N	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17549\\",\\"label\\":\\"KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240\\",\\"postal_code\\":\\"12240\\",\\"full_address\\":\\"jalan bank exim, KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240, 12240\\"},\\"customer_info\\":{\\"social_title\\":\\"Ms.\\",\\"first_name\\":\\"jingga\\",\\"last_name\\":\\"aulia\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":\\"1\\"},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-05T02:27:57.099047Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":300}}"	2025-08-05 02:27:57	2025-08-05 02:30:15	17549	KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240	12240	663f3c00-2ed7-4e6c-bd9d-c6cfc14e2945	\N	\N	\N	0	0.00
6	SF-20250805-BFEVPF	2	Idam Palada	idampalada08@gmail.com	081287809468	confirmed	1000.00	110.00	9000.00	0.00	10110.00	IDR	"Jalan Bank exim no 37"	"Jalan Bank exim no 37"	"Jakarta"	credit_card	paid	\N	\N	\N	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17549\\",\\"label\\":\\"KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240\\",\\"postal_code\\":\\"12240\\",\\"full_address\\":\\"Jalan Bank exim no 37, KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240, 12240\\"},\\"customer_info\\":{\\"social_title\\":null,\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":false},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-05T02:04:50.080738Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":0.5}}"	2025-08-05 02:04:50	2025-08-05 03:19:41	17549	KEBAYORAN LAMA SELATAN, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12240	12240	d7ecda12-e751-43f7-9701-454196505d43	"{\\"status_code\\":\\"200\\",\\"transaction_id\\":\\"e54b0848-7c33-4789-bc74-cb330e014f64\\",\\"gross_amount\\":\\"10110.00\\",\\"currency\\":\\"IDR\\",\\"order_id\\":\\"SF-20250805-BFEVPF\\",\\"payment_type\\":\\"bank_transfer\\",\\"signature_key\\":\\"72970824ee7ef21633d4619ee72b9aabc7bc5f84f11b5afa131107f08f8d8be9c8e91a481e9a21e5b854b44a8072ade842a2c8d865a95261985f9da583da66c8\\",\\"transaction_status\\":\\"settlement\\",\\"fraud_status\\":\\"accept\\",\\"status_message\\":\\"Success, transaction is found\\",\\"merchant_id\\":\\"G729994905\\",\\"va_numbers\\":[{\\"bank\\":\\"bca\\",\\"va_number\\":\\"33339534032702569817693\\"}],\\"payment_amounts\\":[],\\"transaction_time\\":\\"2025-08-05 09:05:03\\",\\"settlement_time\\":\\"2025-08-05 09:05:51\\",\\"expiry_time\\":\\"2025-08-05 12:05:03\\"}"	\N	\N	0	0.00
10	SF-20250805-OUU1SF	2	Palada Idam	idampalada80@gmail.com	081287809468	pending	1000.00	110.00	16200.00	0.00	17310.00	IDR	"Jalan kramat 1 no 37 rt 6"	"Jalan kramat 1 no 37 rt 6"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"mock_generic_001\\",\\"label\\":\\"Kebayoran lama pondok pinang, Indonesia 10000\\",\\"postal_code\\":\\"10000\\",\\"full_address\\":\\"Jalan kramat 1 no 37 rt 6, Kebayoran lama pondok pinang, Indonesia 10000, 10000\\"},\\"customer_info\\":{\\"social_title\\":null,\\"first_name\\":\\"Palada\\",\\"last_name\\":\\"Idam\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":false},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-05T08:12:45.823880Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":0.5}}"	2025-08-05 08:12:45	2025-08-05 08:12:45	mock_generic_001	Kebayoran lama pondok pinang, Indonesia 10000	10000	9147ab46-4817-4620-b66c-99c7d7c3ae2f	\N	\N	\N	0	0.00
11	SF-20250805-UCEMCV	2	Idam Palada	idampalada08@gmail.com	081287809468	pending	1000.00	110.00	16200.00	0.00	17310.00	IDR	"jalan bank exim"	"jalan bank exim"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"mock_generic_001\\",\\"label\\":\\"Kebayoran lama, Indonesia 10000\\",\\"postal_code\\":\\"10000\\",\\"full_address\\":\\"jalan bank exim, Kebayoran lama, Indonesia 10000, 10000\\"},\\"customer_info\\":{\\"social_title\\":null,\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":false},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-05T08:39:57.466587Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":0.5}}"	2025-08-05 08:39:57	2025-08-05 08:39:57	mock_generic_001	Kebayoran lama, Indonesia 10000	10000	1e676fa1-1759-4587-8501-8272d66a072a	\N	\N	\N	0	0.00
9	SF-20250805-GFSH2V	2	palada idam	idampalada80@gmail.com	081287809468	paid	1000.00	110.00	18000.00	0.00	19110.00	IDR	"jalan bank exim"	"jalan bank exim"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: JNE REG - Layanan Reguler	"{\\"shipping_method\\":\\"JNE REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"JNE REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"mock_generic_001\\",\\"label\\":\\"Kebayoran lama, Indonesia 10000\\",\\"postal_code\\":\\"10000\\",\\"full_address\\":\\"jalan bank exim, Kebayoran lama, Indonesia 10000, 10000\\"},\\"customer_info\\":{\\"social_title\\":null,\\"first_name\\":\\"palada\\",\\"last_name\\":\\"idam\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":false},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-05T04:49:44.881692Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":0.5}}"	2025-08-05 04:49:44	2025-08-05 14:07:58	mock_generic_001	Kebayoran lama, Indonesia 10000	10000	9790f4df-ee17-4161-aec7-91f9dc207ef1	\N	\N	\N	0	0.00
12	SF-20250805-DAHTC9	6	gele gele	skipskip@gmail.com	11559094	cancelled	2000.00	220.00	16200.00	0.00	18420.00	IDR	"jalan jalan"	"jalan jalan"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	nasinya banyakin\nShipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"mock_generic_001\\",\\"label\\":\\"Ii, Indonesia 10000\\",\\"postal_code\\":\\"10000\\",\\"full_address\\":\\"jalan jalan, Ii, Indonesia 10000, 10000\\"},\\"customer_info\\":{\\"social_title\\":\\"Ms.\\",\\"first_name\\":\\"gele\\",\\"last_name\\":\\"gele\\",\\"birthdate\\":\\"2009-09-30\\",\\"newsletter_subscribe\\":false},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36 Edg\\\\\\/138.0.0.0\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-05T09:44:33.582656Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":1}}"	2025-08-05 09:44:33	2025-08-05 09:46:36	mock_generic_001	Ii, Indonesia 10000	10000	8e8878af-6c2f-40ab-9248-e9bc2e920284	\N	\N	\N	0	0.00
13	SF-20250805-MDZ6NG	2	Idam Palada Palada	idampalada08@gmail.com	081287809468	paid	2000.00	220.00	16200.00	0.00	18420.00	IDR	"Jalan Bank exim no 7 rt 5 rw 1"	"Jalan Bank exim no 7 rt 5 rw 1"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"mock_generic_001\\",\\"label\\":\\"Kebayoran lama, Indonesia 10000\\",\\"postal_code\\":\\"10000\\",\\"full_address\\":\\"Jalan Bank exim no 7 rt 5 rw 1, Kebayoran lama, Indonesia 10000, 10000\\"},\\"customer_info\\":{\\"social_title\\":\\"Mr.\\",\\"first_name\\":\\"Idam Palada\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":\\"2002-07-08\\",\\"newsletter_subscribe\\":false},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-05T14:23:30.801994Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":1}}"	2025-08-05 14:23:30	2025-08-05 14:25:12	mock_generic_001	Kebayoran lama, Indonesia 10000	10000	38e0d0dd-7a63-4ef9-bcd4-cb790109b6dc	\N	\N	\N	0	0.00
15	SF-20250805-LB4DZU	2	Idam Paladaaaaa	idampalada08@gmail.com	081287809698	paid	1000.00	110.00	16200.00	0.00	17310.00	IDR	"Jalan bank ecim"	"Jalan bank ecim"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"mock_generic_001\\",\\"label\\":\\"Kebayoran lama, Indonesia 10000\\",\\"postal_code\\":\\"10000\\",\\"full_address\\":\\"Jalan bank ecim, Kebayoran lama, Indonesia 10000, 10000\\"},\\"customer_info\\":{\\"social_title\\":null,\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Paladaaaaa\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":false},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-05T14:58:14.796107Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":0.5}}"	2025-08-05 21:58:14	2025-08-06 09:05:57	mock_generic_001	Kebayoran lama, Indonesia 10000	10000	70d15c73-40e6-4395-b3db-1ed61699f55e	\N	\N	\N	0	0.00
16	SF-20250805-SAQVQY	2	palada madii	idampalada08@gmail.com	081287809468	paid	1000.00	110.00	16200.00	0.00	17310.00	IDR	"jalan bank eximmm"	"jalan bank eximmm"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"mock_generic_001\\",\\"label\\":\\"Kebayoran lama, Indonesia 10000\\",\\"postal_code\\":\\"10000\\",\\"full_address\\":\\"jalan bank eximmm, Kebayoran lama, Indonesia 10000, 10000\\"},\\"customer_info\\":{\\"social_title\\":null,\\"first_name\\":\\"palada\\",\\"last_name\\":\\"madii\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":false},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-05T15:08:56.784170Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":0.5}}"	2025-08-05 22:08:56	2025-08-06 08:51:01	mock_generic_001	Kebayoran lama, Indonesia 10000	10000	c229b0af-8ab8-4703-83e3-a15551d7e3f3	"{\\"va_numbers\\":[{\\"bank\\":\\"bca\\",\\"va_number\\":\\"33339993905578337564693\\"}],\\"transaction_time\\":\\"2025-08-05 22:09:01\\",\\"transaction_status\\":\\"settlement\\",\\"transaction_id\\":\\"92dbc990-f79c-436f-8691-1cefa5363313\\",\\"status_message\\":\\"Success, transaction is found\\",\\"status_code\\":\\"200\\",\\"signature_key\\":\\"fa12fdcdc4d68a0aaa52164b876b987caaf84868a5c2e1f6c09cf8cd94856c6a463d5564114e592be0213f21069f3031c40fdafd68b6c3b4c366045b6d6abf51\\",\\"payment_type\\":\\"bank_transfer\\",\\"payment_amounts\\":[],\\"order_id\\":\\"SF-20250805-SAQVQY\\",\\"merchant_id\\":\\"G729994905\\",\\"gross_amount\\":\\"17310.00\\",\\"fraud_status\\":\\"accept\\",\\"expiry_time\\":\\"2025-08-06 01:09:01\\",\\"currency\\":\\"IDR\\",\\"settlement_time\\":\\"2025-08-05 22:09:30\\"}"	\N	\N	0	0.00
14	SF-20250805-3AVYRE	2	Idam Paladaa	idampalada08@gmail.com	081287809468	paid	1000.00	110.00	16200.00	0.00	17310.00	IDR	"jalan bank exim"	"jalan bank exim"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"mock_generic_001\\",\\"label\\":\\"Kebayoran, Indonesia 10000\\",\\"postal_code\\":\\"10000\\",\\"full_address\\":\\"jalan bank exim, Kebayoran, Indonesia 10000, 10000\\"},\\"customer_info\\":{\\"social_title\\":\\"Mr.\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Paladaa\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":false},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-05T14:44:23.003954Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":0.5}}"	2025-08-05 21:44:23	2025-08-06 08:59:24	mock_generic_001	Kebayoran, Indonesia 10000	10000	fc407b96-a85a-4c6e-8b9c-8a61cf13543c	\N	\N	\N	0	0.00
18	SF-20250807-QIFTEV	2	Idam Palada	idampalada08@gmail.com	081287809468	pending	1000.00	110.00	9000.00	0.00	10110.00	IDR	"jalan bank exim"	"jalan bank exim"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17551\\",\\"label\\":\\"PONDOK PINANG, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12310\\",\\"postal_code\\":\\"12310\\",\\"full_address\\":\\"jalan bank exim, PONDOK PINANG, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12310, 12310\\"},\\"customer_info\\":{\\"gender\\":\\"mens\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":false,\\"account_created\\":false,\\"existing_user\\":true},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-07T15:37:08.203953Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":0.5,\\"subtotal_breakdown\\":{\\"items_subtotal\\":1000,\\"shipping_cost\\":9000,\\"tax_amount\\":110,\\"total_amount\\":10110}}}"	2025-08-07 22:37:08	2025-08-07 22:37:08	17551	PONDOK PINANG, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12310	12310	66696ee9-df26-41d1-b0ef-9bcbec103a87	\N	\N	\N	0	0.00
17	SF-20250806-SYNZEG	2	Idam palada	idampalada08@gmail.com	081287809468	paid	2000.00	220.00	9000.00	0.00	11220.00	IDR	"Jalan bank exm no 37"	"Jalan bank exm no 37"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17551\\",\\"label\\":\\"PONDOK PINANG, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12310\\",\\"postal_code\\":\\"12310\\",\\"full_address\\":\\"Jalan bank exm no 37, PONDOK PINANG, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12310, 12310\\"},\\"customer_info\\":{\\"social_title\\":null,\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"palada\\",\\"birthdate\\":null,\\"newsletter_subscribe\\":false},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-06T02:09:47.489837Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":1}}"	2025-08-06 09:09:47	2025-08-06 09:11:59	17551	PONDOK PINANG, KEBAYORAN LAMA, JAKARTA SELATAN, DKI JAKARTA, 12310	12310	8b191661-0a00-4413-8188-ec51d9678a56	"{\\"va_numbers\\":[{\\"bank\\":\\"bca\\",\\"va_number\\":\\"33339500483816687216674\\"}],\\"transaction_time\\":\\"2025-08-06 09:11:05\\",\\"transaction_status\\":\\"settlement\\",\\"transaction_id\\":\\"84eb635c-a602-4ed0-9083-30747b8341e4\\",\\"status_message\\":\\"Success, transaction is found\\",\\"status_code\\":\\"200\\",\\"signature_key\\":\\"b68fb814c80562c8bad08724a800786b0ef6c8f806781762e757c39f49dd1d95f077e92c69dae05ac4db40d6127f061ac1b0d79a8478dc63d177accdfefa6139\\",\\"settlement_time\\":\\"2025-08-06 09:11:57\\",\\"payment_type\\":\\"bank_transfer\\",\\"payment_amounts\\":[],\\"order_id\\":\\"SF-20250806-SYNZEG\\",\\"merchant_id\\":\\"G729994905\\",\\"gross_amount\\":\\"11220.00\\",\\"fraud_status\\":\\"accept\\",\\"expiry_time\\":\\"2025-08-06 12:11:05\\",\\"currency\\":\\"IDR\\"}"	\N	\N	0	0.00
24	SF-20250814-L2C2IX	2	Idam Palada	idampalada08@gmail.com	081287809468	cancelled	1299000.00	0.00	10000.00	0.00	1309000.00	IDR	"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110"	"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: JNE REG - Layanan Reguler	"{\\"shipping_method\\":\\"JNE REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"JNE REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17544\\",\\"label\\":\\"DKI JAKARTA, JAKARTA SELATAN, SELONG, 12110\\",\\"postal_code\\":\\"12110\\",\\"full_address\\":\\"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110\\"},\\"address_info\\":{\\"address_id\\":2,\\"label\\":\\"Rumah\\",\\"recipient_name\\":\\"Idam Palada\\",\\"phone_recipient\\":\\"081287809468\\",\\"province_name\\":\\"DKI JAKARTA\\",\\"city_name\\":\\"JAKARTA SELATAN\\",\\"subdistrict_name\\":\\"SELONG\\",\\"street_address\\":\\"Jl Pattimura 20, Kementerian pekerjaan umum\\",\\"saved_address_used\\":true,\\"address_saved\\":false,\\"set_as_primary\\":false},\\"customer_info\\":{\\"gender\\":\\"mens\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":\\"2002-07-08\\",\\"newsletter_subscribe\\":false,\\"account_created\\":false,\\"existing_user\\":true},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout_with_address_integration_no_cod_no_tax\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-13T17:13:59.100067Z\\",\\"tax_rate\\":0,\\"cart_items_count\\":1,\\"total_weight\\":500,\\"subtotal_breakdown\\":{\\"items_subtotal\\":1299000,\\"shipping_cost\\":10000,\\"tax_amount\\":0,\\"total_amount\\":1309000}}}"	2025-08-14 00:13:59	2025-08-14 03:15:10	17544	DKI JAKARTA, JAKARTA SELATAN, SELONG, 12110	12110	68cc972c-83de-4ba6-b7dc-cda39b474fd3	"{\\"va_numbers\\":[{\\"bank\\":\\"bca\\",\\"va_number\\":\\"33339854971998809127053\\"}],\\"transaction_time\\":\\"2025-08-14 00:14:04\\",\\"transaction_status\\":\\"expire\\",\\"transaction_id\\":\\"86462596-6632-41cc-8440-7aad4f8f7418\\",\\"status_message\\":\\"Success, transaction is found\\",\\"status_code\\":\\"407\\",\\"signature_key\\":\\"3a2afd1c0146103f1e89878d3d3114daba2004bb152cf7d65bfd3d2f72e9bf68883136c8858b54cf5d6f82fff6698c81f87eb6a1765baf8eca16232abecc0851\\",\\"payment_type\\":\\"bank_transfer\\",\\"payment_amounts\\":[],\\"order_id\\":\\"SF-20250814-L2C2IX\\",\\"merchant_id\\":\\"G729994905\\",\\"gross_amount\\":\\"1309000.00\\",\\"fraud_status\\":\\"accept\\",\\"expiry_time\\":\\"2025-08-14 03:14:04\\",\\"currency\\":\\"IDR\\"}"	\N	\N	0	0.00
19	SF-20250809-VHSKNJ	2	Idam Palada	idampalada08@gmail.comm	081287809468	pending	30000.00	3300.00	10000.00	0.00	43300.00	IDR	"Jalan Bank exim no 37 Rt  Rw 1, PONDOK PINANG, JAKARTA SELATAN, DKI JAKARTA 12310"	"Jalan Bank exim no 37 Rt  Rw 1, PONDOK PINANG, JAKARTA SELATAN, DKI JAKARTA 12310"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: JNE REG - Layanan Reguler	"{\\"shipping_method\\":\\"JNE REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"JNE REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17551\\",\\"label\\":\\"DKI JAKARTA, JAKARTA SELATAN, PONDOK PINANG, 12310\\",\\"postal_code\\":\\"12310\\",\\"full_address\\":\\"Jalan Bank exim no 37 Rt  Rw 1, PONDOK PINANG, JAKARTA SELATAN, DKI JAKARTA 12310\\"},\\"address_info\\":{\\"address_id\\":1,\\"label\\":\\"Rumah\\",\\"recipient_name\\":\\"Idam\\",\\"phone_recipient\\":\\"081287809468\\",\\"province_name\\":\\"DKI JAKARTA\\",\\"city_name\\":\\"JAKARTA SELATAN\\",\\"subdistrict_name\\":\\"PONDOK PINANG\\",\\"street_address\\":\\"Jalan Bank exim no 37 Rt  Rw 1\\",\\"saved_address_used\\":true,\\"address_saved\\":false,\\"set_as_primary\\":false},\\"customer_info\\":{\\"gender\\":\\"mens\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":\\"2002-07-08\\",\\"newsletter_subscribe\\":false,\\"account_created\\":false,\\"existing_user\\":true},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout_with_address_integration\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-09T10:39:58.573578Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":0.5,\\"subtotal_breakdown\\":{\\"items_subtotal\\":30000,\\"shipping_cost\\":10000,\\"tax_amount\\":3300,\\"total_amount\\":43300}}}"	2025-08-09 17:39:58	2025-08-09 17:39:58	17551	DKI JAKARTA, JAKARTA SELATAN, PONDOK PINANG, 12310	12310	45f2d160-54db-4067-9fea-41bfaab49b1f	\N	\N	\N	0	0.00
21	SF-20250810-ZMTDIZ	2	Idam Pld	idampalada08@gmail.com	081287809468	cancelled	1000.00	110.00	10000.00	0.00	11110.00	IDR	"Jalan Bank exim no 37 Rt  Rw 1, PONDOK PINANG, JAKARTA SELATAN, DKI JAKARTA 12310"	"Jalan Bank exim no 37 Rt  Rw 1, PONDOK PINANG, JAKARTA SELATAN, DKI JAKARTA 12310"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: JNE REG - Layanan Reguler	"{\\"shipping_method\\":\\"JNE REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"JNE REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17551\\",\\"label\\":\\"DKI JAKARTA, JAKARTA SELATAN, PONDOK PINANG, 12310\\",\\"postal_code\\":\\"12310\\",\\"full_address\\":\\"Jalan Bank exim no 37 Rt  Rw 1, PONDOK PINANG, JAKARTA SELATAN, DKI JAKARTA 12310\\"},\\"address_info\\":{\\"address_id\\":1,\\"label\\":\\"Rumah\\",\\"recipient_name\\":\\"Idam\\",\\"phone_recipient\\":\\"081287809468\\",\\"province_name\\":\\"DKI JAKARTA\\",\\"city_name\\":\\"JAKARTA SELATAN\\",\\"subdistrict_name\\":\\"PONDOK PINANG\\",\\"street_address\\":\\"Jalan Bank exim no 37 Rt  Rw 1\\",\\"saved_address_used\\":true,\\"address_saved\\":false,\\"set_as_primary\\":false},\\"customer_info\\":{\\"gender\\":\\"mens\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Pld\\",\\"birthdate\\":\\"2002-07-08\\",\\"newsletter_subscribe\\":false,\\"account_created\\":false,\\"existing_user\\":true},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout_with_address_integration_no_cod\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (iPhone; CPU iPhone OS 17_6_1 like Mac OS X) AppleWebKit\\\\\\/605.1.15 (KHTML, like Gecko) Version\\\\\\/17.6 Mobile\\\\\\/15E148 Safari\\\\\\/604.1\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-10T15:05:34.231808Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":0.5,\\"subtotal_breakdown\\":{\\"items_subtotal\\":1000,\\"shipping_cost\\":10000,\\"tax_amount\\":110,\\"total_amount\\":11110}}}"	2025-08-10 22:05:34	2025-08-11 01:07:04	17551	DKI JAKARTA, JAKARTA SELATAN, PONDOK PINANG, 12310	12310	985326fa-97fc-4d83-b15e-641981a2667e	"{\\"va_numbers\\":[{\\"bank\\":\\"bri\\",\\"va_number\\":\\"124121845483814549\\"}],\\"transaction_time\\":\\"2025-08-10 22:06:01\\",\\"transaction_status\\":\\"expire\\",\\"transaction_id\\":\\"707c01a5-d3e0-4d14-96a2-30be37360c0c\\",\\"status_message\\":\\"Success, transaction is found\\",\\"status_code\\":\\"407\\",\\"signature_key\\":\\"23462106aff5fc95ed5711eaba81371c79ba16e752e3918b9f66af612a4e3346935e6f40e7e4e27fdfa6364014112c26e0646d03a0c5770518a23dea57ae6c24\\",\\"payment_type\\":\\"bank_transfer\\",\\"payment_amounts\\":[],\\"order_id\\":\\"SF-20250810-ZMTDIZ\\",\\"merchant_id\\":\\"G729994905\\",\\"gross_amount\\":\\"11110.00\\",\\"fraud_status\\":\\"accept\\",\\"expiry_time\\":\\"2025-08-11 01:06:01\\",\\"currency\\":\\"IDR\\"}"	\N	\N	0	0.00
20	SF-20250809-M7ZTEM	2	Idam Palada	idampalada08@gmail.com	081287809468	cancelled	1300000.00	143000.00	9000.00	0.00	1452000.00	IDR	"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110"	"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: SICEPAT REG - Layanan Reguler	"{\\"shipping_method\\":\\"SICEPAT REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"SICEPAT REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17544\\",\\"label\\":\\"DKI JAKARTA, JAKARTA SELATAN, SELONG, 12110\\",\\"postal_code\\":\\"12110\\",\\"full_address\\":\\"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110\\"},\\"address_info\\":{\\"address_id\\":2,\\"label\\":\\"Rumah\\",\\"recipient_name\\":\\"Idam Palada\\",\\"phone_recipient\\":\\"081287809468\\",\\"province_name\\":\\"DKI JAKARTA\\",\\"city_name\\":\\"JAKARTA SELATAN\\",\\"subdistrict_name\\":\\"SELONG\\",\\"street_address\\":\\"Jl Pattimura 20, Kementerian pekerjaan umum\\",\\"saved_address_used\\":true,\\"address_saved\\":false,\\"set_as_primary\\":false},\\"customer_info\\":{\\"gender\\":\\"mens\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":\\"2002-07-08\\",\\"newsletter_subscribe\\":false,\\"account_created\\":false,\\"existing_user\\":true},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout_with_address_integration_no_cod\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-09T10:52:04.013815Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":300,\\"subtotal_breakdown\\":{\\"items_subtotal\\":1300000,\\"shipping_cost\\":9000,\\"tax_amount\\":143000,\\"total_amount\\":1452000}}}"	2025-08-09 17:52:04	2025-08-09 20:53:11	17544	DKI JAKARTA, JAKARTA SELATAN, SELONG, 12110	12110	cf9681a0-edcb-4e75-9572-6e6a0a788b67	"{\\"va_numbers\\":[{\\"bank\\":\\"bca\\",\\"va_number\\":\\"33339202587623081931263\\"}],\\"transaction_time\\":\\"2025-08-09 17:52:07\\",\\"transaction_status\\":\\"expire\\",\\"transaction_id\\":\\"dee42fc7-5e94-40cc-ba56-8f8c14a64fc2\\",\\"status_message\\":\\"Success, transaction is found\\",\\"status_code\\":\\"407\\",\\"signature_key\\":\\"88d2ae056cff8fa3799f5886d82af5b1e8b780c40ff9eb0a39e3be5e7cbcfa9b86f7d8377055195e923b35c46aff7ad82766649c6f0aeac6f548043335c2a03c\\",\\"payment_type\\":\\"bank_transfer\\",\\"payment_amounts\\":[],\\"order_id\\":\\"SF-20250809-M7ZTEM\\",\\"merchant_id\\":\\"G729994905\\",\\"gross_amount\\":\\"1452000.00\\",\\"fraud_status\\":\\"accept\\",\\"expiry_time\\":\\"2025-08-09 20:52:07\\",\\"currency\\":\\"IDR\\"}"	\N	\N	0	0.00
22	SF-20250812-IUTAHE	2	Idam Palada	idampalada08@gmail.com	081287809468	cancelled	1299000.00	142890.00	12000.00	0.00	1453890.00	IDR	"Jl blbablaba, CINANGKA, DEPOK, JAWA BARAT 16516"	"Jl blbablaba, CINANGKA, DEPOK, JAWA BARAT 16516"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: JNE REG - Layanan Reguler	"{\\"shipping_method\\":\\"JNE REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"JNE REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"25970\\",\\"label\\":\\"JAWA BARAT, DEPOK, CINANGKA, 16516\\",\\"postal_code\\":\\"16516\\",\\"full_address\\":\\"Jl blbablaba, CINANGKA, DEPOK, JAWA BARAT 16516\\"},\\"address_info\\":{\\"address_id\\":3,\\"label\\":\\"Rumah\\",\\"recipient_name\\":\\"Indra\\",\\"phone_recipient\\":\\"081287809468\\",\\"province_name\\":\\"JAWA BARAT\\",\\"city_name\\":\\"DEPOK\\",\\"subdistrict_name\\":\\"CINANGKA\\",\\"street_address\\":\\"Jl blbablaba\\",\\"saved_address_used\\":true,\\"address_saved\\":false,\\"set_as_primary\\":false},\\"customer_info\\":{\\"gender\\":\\"mens\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":\\"2002-07-08\\",\\"newsletter_subscribe\\":false,\\"account_created\\":false,\\"existing_user\\":true},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout_with_address_integration_no_cod\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-12T06:50:07.139587Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":500,\\"subtotal_breakdown\\":{\\"items_subtotal\\":1299000,\\"shipping_cost\\":12000,\\"tax_amount\\":142890,\\"total_amount\\":1453890}}}"	2025-08-12 13:50:07	2025-08-12 16:51:20	25970	JAWA BARAT, DEPOK, CINANGKA, 16516	16516	eb92da29-fdc0-4fd6-a62b-8aa3db60faaf	"{\\"va_numbers\\":[{\\"bank\\":\\"bca\\",\\"va_number\\":\\"33339326513311177885364\\"}],\\"transaction_time\\":\\"2025-08-12 13:50:13\\",\\"transaction_status\\":\\"expire\\",\\"transaction_id\\":\\"13287219-54a5-4ece-bcda-52ac6b72563d\\",\\"status_message\\":\\"Success, transaction is found\\",\\"status_code\\":\\"407\\",\\"signature_key\\":\\"47d423b5bd55e4dd698337528e8c8b13acda5a41eeaf3123f2a8d4a001eb418193581f7d9f460d066c08c8bd5d33eb70e7468f8575d991d29e793cfc299320dc\\",\\"payment_type\\":\\"bank_transfer\\",\\"payment_amounts\\":[],\\"order_id\\":\\"SF-20250812-IUTAHE\\",\\"merchant_id\\":\\"G729994905\\",\\"gross_amount\\":\\"1453890.00\\",\\"fraud_status\\":\\"accept\\",\\"expiry_time\\":\\"2025-08-12 16:50:13\\",\\"currency\\":\\"IDR\\"}"	\N	\N	0	0.00
23	SF-20250813-EKMHBB	2	Idam Palada	idampalada08@gmail.com	081287809468	cancelled	649000.00	71390.00	10000.00	0.00	730390.00	IDR	"Jalan Bank exim no 37 Rt  Rw 1, PONDOK PINANG, JAKARTA SELATAN, DKI JAKARTA 12310"	"Jalan Bank exim no 37 Rt  Rw 1, PONDOK PINANG, JAKARTA SELATAN, DKI JAKARTA 12310"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: JNE REG - Layanan Reguler	"{\\"shipping_method\\":\\"JNE REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"JNE REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17551\\",\\"label\\":\\"DKI JAKARTA, JAKARTA SELATAN, PONDOK PINANG, 12310\\",\\"postal_code\\":\\"12310\\",\\"full_address\\":\\"Jalan Bank exim no 37 Rt  Rw 1, PONDOK PINANG, JAKARTA SELATAN, DKI JAKARTA 12310\\"},\\"address_info\\":{\\"address_id\\":1,\\"label\\":\\"Rumah\\",\\"recipient_name\\":\\"Idam\\",\\"phone_recipient\\":\\"081287809468\\",\\"province_name\\":\\"DKI JAKARTA\\",\\"city_name\\":\\"JAKARTA SELATAN\\",\\"subdistrict_name\\":\\"PONDOK PINANG\\",\\"street_address\\":\\"Jalan Bank exim no 37 Rt  Rw 1\\",\\"saved_address_used\\":true,\\"address_saved\\":false,\\"set_as_primary\\":false},\\"customer_info\\":{\\"gender\\":\\"mens\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":\\"2002-07-08\\",\\"newsletter_subscribe\\":false,\\"account_created\\":false,\\"existing_user\\":true},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout_with_address_integration_no_cod\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-13T12:45:53.211264Z\\",\\"tax_rate\\":0.11,\\"cart_items_count\\":1,\\"total_weight\\":500,\\"subtotal_breakdown\\":{\\"items_subtotal\\":649000,\\"shipping_cost\\":10000,\\"tax_amount\\":71390,\\"total_amount\\":730390}}}"	2025-08-13 19:45:53	2025-08-13 22:47:02	17551	DKI JAKARTA, JAKARTA SELATAN, PONDOK PINANG, 12310	12310	f12b6413-dfcb-4c53-89b0-cead7ec0bb68	"{\\"va_numbers\\":[{\\"bank\\":\\"bca\\",\\"va_number\\":\\"33339195623959910282764\\"}],\\"transaction_time\\":\\"2025-08-13 19:45:58\\",\\"transaction_status\\":\\"expire\\",\\"transaction_id\\":\\"8a001bef-68b9-48e0-aa4c-5bdb6d9a20a5\\",\\"status_message\\":\\"Success, transaction is found\\",\\"status_code\\":\\"407\\",\\"signature_key\\":\\"2ccb1a67c0a2271228eeccc16279f2b9d13edc363b47532eb01899fa6dc28120667ec8bdaf67a1670df1b17fff5bf264bdcb5b3e37bb2571638951caa4a74c3d\\",\\"payment_type\\":\\"bank_transfer\\",\\"payment_amounts\\":[],\\"order_id\\":\\"SF-20250813-EKMHBB\\",\\"merchant_id\\":\\"G729994905\\",\\"gross_amount\\":\\"730390.00\\",\\"fraud_status\\":\\"accept\\",\\"expiry_time\\":\\"2025-08-13 22:45:58\\",\\"currency\\":\\"IDR\\"}"	\N	\N	0	0.00
25	SF-20250814-TJXPYG	2	Idam Palaget	idampalada08@gmail.com	081287809468	cancelled	2999000.00	0.00	10000.00	100000.00	2909000.00	IDR	"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110"	"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: JNE REG - Layanan Reguler	"{\\"shipping_method\\":\\"JNE REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"JNE REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17544\\",\\"label\\":\\"DKI JAKARTA, JAKARTA SELATAN, SELONG, 12110\\",\\"postal_code\\":\\"12110\\",\\"full_address\\":\\"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110\\"},\\"address_info\\":{\\"address_id\\":2,\\"label\\":\\"Rumah\\",\\"recipient_name\\":\\"Idam Palada\\",\\"phone_recipient\\":\\"081287809468\\",\\"province_name\\":\\"DKI JAKARTA\\",\\"city_name\\":\\"JAKARTA SELATAN\\",\\"subdistrict_name\\":\\"SELONG\\",\\"street_address\\":\\"Jl Pattimura 20, Kementerian pekerjaan umum\\",\\"saved_address_used\\":true,\\"address_saved\\":false,\\"set_as_primary\\":false},\\"customer_info\\":{\\"gender\\":\\"mens\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palaget\\",\\"birthdate\\":\\"2002-07-08\\",\\"newsletter_subscribe\\":false,\\"account_created\\":false,\\"existing_user\\":true},\\"coupon_info\\":{\\"code\\":\\"WELCOME10\\",\\"discount_amount\\":100000,\\"source\\":\\"form_data\\"},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout_with_address_integration_no_cod_no_tax_simple_coupon\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/138.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-14T09:07:42.850129Z\\",\\"tax_rate\\":0,\\"cart_items_count\\":1,\\"total_weight\\":500,\\"subtotal_breakdown\\":{\\"items_subtotal\\":2999000,\\"shipping_cost\\":10000,\\"tax_amount\\":0,\\"discount_amount\\":100000,\\"total_amount\\":2909000}}}"	2025-08-14 16:07:42	2025-08-14 19:08:47	17544	DKI JAKARTA, JAKARTA SELATAN, SELONG, 12110	12110	6f6c82fe-f753-4146-a234-6b1ef7165018	"{\\"va_numbers\\":[{\\"bank\\":\\"bca\\",\\"va_number\\":\\"33339468782069485032373\\"}],\\"transaction_time\\":\\"2025-08-14 16:07:45\\",\\"transaction_status\\":\\"expire\\",\\"transaction_id\\":\\"aba3d332-023e-4849-9a18-2926bc9cf219\\",\\"status_message\\":\\"Success, transaction is found\\",\\"status_code\\":\\"407\\",\\"signature_key\\":\\"3d1b8627fcf567994110d27ed669cc00b485aa0cb57a80a60d3828d929d866fbaed9cb595a4d8582892d7109ea481db04c3222e2747e19d85ac83a5785870cd9\\",\\"payment_type\\":\\"bank_transfer\\",\\"payment_amounts\\":[],\\"order_id\\":\\"SF-20250814-TJXPYG\\",\\"merchant_id\\":\\"G729994905\\",\\"gross_amount\\":\\"2909000.00\\",\\"fraud_status\\":\\"accept\\",\\"expiry_time\\":\\"2025-08-14 19:07:45\\",\\"currency\\":\\"IDR\\"}"	\N	\N	0	0.00
26	SF-20250815-EBGCAW	2	Idam Palada	idampalada08@gmail.com	081287809468	cancelled	2500000.00	0.00	12000.00	0.00	2512000.00	IDR	"Jl blbablaba, CINANGKA, DEPOK, JAWA BARAT 16516"	"Jl blbablaba, CINANGKA, DEPOK, JAWA BARAT 16516"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: JNE REG - Layanan Reguler	"{\\"shipping_method\\":\\"JNE REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"JNE REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"25970\\",\\"label\\":\\"JAWA BARAT, DEPOK, CINANGKA, 16516\\",\\"postal_code\\":\\"16516\\",\\"full_address\\":\\"Jl blbablaba, CINANGKA, DEPOK, JAWA BARAT 16516\\"},\\"address_info\\":{\\"address_id\\":3,\\"label\\":\\"Rumah\\",\\"recipient_name\\":\\"Indra\\",\\"phone_recipient\\":\\"081287809468\\",\\"province_name\\":\\"JAWA BARAT\\",\\"city_name\\":\\"DEPOK\\",\\"subdistrict_name\\":\\"CINANGKA\\",\\"street_address\\":\\"Jl blbablaba\\",\\"saved_address_used\\":true,\\"address_saved\\":false,\\"set_as_primary\\":false},\\"customer_info\\":{\\"gender\\":\\"mens\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":\\"2002-07-08\\",\\"newsletter_subscribe\\":false,\\"account_created\\":false,\\"existing_user\\":true},\\"coupon_info\\":null,\\"checkout_info\\":{\\"created_via\\":\\"web_checkout_with_address_integration_no_cod_no_tax_simple_coupon\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/139.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-15T07:09:42.228328Z\\",\\"tax_rate\\":0,\\"cart_items_count\\":1,\\"total_weight\\":500,\\"subtotal_breakdown\\":{\\"items_subtotal\\":2500000,\\"shipping_cost\\":12000,\\"tax_amount\\":0,\\"discount_amount\\":0,\\"total_amount\\":2512000}}}"	2025-08-15 14:09:42	2025-08-15 22:11:43	25970	JAWA BARAT, DEPOK, CINANGKA, 16516	16516	fbdccba0-2609-4071-9e6c-bc77ea55c9a1	\N	\N	\N	0	0.00
27	SF-20250815-2AWY2O	2	Idam pALAGET	idampalada08@gmail.com	081287809468	cancelled	2500000.00	0.00	12000.00	0.00	2512000.00	IDR	"Jl blbablaba, CINANGKA, DEPOK, JAWA BARAT 16516"	"Jl blbablaba, CINANGKA, DEPOK, JAWA BARAT 16516"	"Jakarta"	credit_card	pending	\N	\N	\N	\N	\N	Shipping: JNE REG - Layanan Reguler	"{\\"shipping_method\\":\\"JNE REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"JNE REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"25970\\",\\"label\\":\\"JAWA BARAT, DEPOK, CINANGKA, 16516\\",\\"postal_code\\":\\"16516\\",\\"full_address\\":\\"Jl blbablaba, CINANGKA, DEPOK, JAWA BARAT 16516\\"},\\"address_info\\":{\\"address_id\\":3,\\"label\\":\\"Rumah\\",\\"recipient_name\\":\\"Indra\\",\\"phone_recipient\\":\\"081287809468\\",\\"province_name\\":\\"JAWA BARAT\\",\\"city_name\\":\\"DEPOK\\",\\"subdistrict_name\\":\\"CINANGKA\\",\\"street_address\\":\\"Jl blbablaba\\",\\"saved_address_used\\":true,\\"address_saved\\":false,\\"set_as_primary\\":false},\\"customer_info\\":{\\"gender\\":\\"mens\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"pALAGET\\",\\"birthdate\\":\\"2002-07-08\\",\\"newsletter_subscribe\\":false,\\"account_created\\":false,\\"existing_user\\":true},\\"coupon_info\\":null,\\"checkout_info\\":{\\"created_via\\":\\"web_checkout_with_address_integration_no_cod_no_tax_simple_coupon\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/139.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-15T08:02:43.005706Z\\",\\"tax_rate\\":0,\\"cart_items_count\\":1,\\"total_weight\\":500,\\"subtotal_breakdown\\":{\\"items_subtotal\\":2500000,\\"shipping_cost\\":12000,\\"tax_amount\\":0,\\"discount_amount\\":0,\\"total_amount\\":2512000}}}"	2025-08-15 15:02:43	2025-08-16 01:12:53	25970	JAWA BARAT, DEPOK, CINANGKA, 16516	16516	2ba1404e-5951-4a25-adc7-28793132cf08	"{\\"va_numbers\\":[{\\"bank\\":\\"bca\\",\\"va_number\\":\\"33339819399925233289903\\"}],\\"transaction_time\\":\\"2025-08-15 22:11:52\\",\\"transaction_status\\":\\"expire\\",\\"transaction_id\\":\\"1ac2207c-cc0d-461c-bcbb-e49b69a677c2\\",\\"status_message\\":\\"Success, transaction is found\\",\\"status_code\\":\\"407\\",\\"signature_key\\":\\"477d4b7cc5f46a74d1051bc809d8fa7429fa8582e1af8594e4728cee75fe913fb60f93ef229277019060e7a4cec8b4c50fc64c54c19b715acdd0088901b0090b\\",\\"payment_type\\":\\"bank_transfer\\",\\"payment_amounts\\":[],\\"order_id\\":\\"SF-20250815-2AWY2O\\",\\"merchant_id\\":\\"G729994905\\",\\"gross_amount\\":\\"2512000.00\\",\\"fraud_status\\":\\"accept\\",\\"expiry_time\\":\\"2025-08-16 01:11:51\\",\\"currency\\":\\"IDR\\"}"	\N	\N	0	0.00
29	SF-20250815-TQHLTU	2	Idam Palada	idampalada08@gmail.com	081287809468	cancelled	2799000.00	0.00	10000.00	15000.00	2794000.00	IDR	"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110"	"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110"	"Jakarta"	credit_card	pending	\N	https://app.midtrans.com/snap/v4/redirection/9ef94b35-7bf7-40aa-b9c3-6713712823df	\N	\N	\N	Shipping: JNE REG - Layanan Reguler	"{\\"shipping_method\\":\\"JNE REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"JNE REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17544\\",\\"label\\":\\"DKI JAKARTA, JAKARTA SELATAN, SELONG, 12110\\",\\"postal_code\\":\\"12110\\",\\"full_address\\":\\"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110\\"},\\"address_info\\":{\\"address_id\\":2,\\"label\\":\\"Rumah\\",\\"recipient_name\\":\\"Idam Palada\\",\\"phone_recipient\\":\\"081287809468\\",\\"province_name\\":\\"DKI JAKARTA\\",\\"city_name\\":\\"JAKARTA SELATAN\\",\\"subdistrict_name\\":\\"SELONG\\",\\"street_address\\":\\"Jl Pattimura 20, Kementerian pekerjaan umum\\",\\"saved_address_used\\":true,\\"address_saved\\":false,\\"set_as_primary\\":false},\\"customer_info\\":{\\"gender\\":\\"mens\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":\\"2002-07-08\\",\\"newsletter_subscribe\\":false,\\"account_created\\":false,\\"existing_user\\":true},\\"voucher_info\\":{\\"voucher_code\\":\\"TANPA_KODE\\",\\"discount_amount\\":15000,\\"source\\":\\"form_data\\"},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout_with_address_integration_no_cod_no_tax_voucher_system\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/139.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-15T14:09:06.997556Z\\",\\"tax_rate\\":0,\\"cart_items_count\\":1,\\"total_weight\\":500,\\"subtotal_breakdown\\":{\\"items_subtotal\\":2799000,\\"shipping_cost\\":10000,\\"tax_amount\\":0,\\"discount_amount\\":15000,\\"total_amount\\":2794000}}}"	2025-08-15 21:09:06	2025-08-16 00:10:15	17544	DKI JAKARTA, JAKARTA SELATAN, SELONG, 12110	12110	9ef94b35-7bf7-40aa-b9c3-6713712823df	"{\\"va_numbers\\":[{\\"bank\\":\\"bca\\",\\"va_number\\":\\"33339672168557594692708\\"}],\\"transaction_time\\":\\"2025-08-15 21:09:12\\",\\"transaction_status\\":\\"expire\\",\\"transaction_id\\":\\"02f7d2ab-c556-4c70-a939-b912dd9852be\\",\\"status_message\\":\\"Success, transaction is found\\",\\"status_code\\":\\"407\\",\\"signature_key\\":\\"d7e414fec4a5d6b3e969978fa39bc0ea08a0a20d95838092995e958518a4d20b3e194de8b4a05edb2bbd936c69d27fd6bee2d4022bc364b372ff9dc5731cceac\\",\\"payment_type\\":\\"bank_transfer\\",\\"payment_amounts\\":[],\\"order_id\\":\\"SF-20250815-TQHLTU\\",\\"merchant_id\\":\\"G729994905\\",\\"gross_amount\\":\\"2794000.00\\",\\"fraud_status\\":\\"accept\\",\\"expiry_time\\":\\"2025-08-16 00:09:12\\",\\"currency\\":\\"IDR\\"}"	\N	\N	0	0.00
28	SF-20250815-IW8HPF	2	Idam Palaget	idampalada08@gmail.com	081287809468	cancelled	2799000.00	0.00	12000.00	15000.00	2796000.00	IDR	"Jl blbablaba, CINANGKA, DEPOK, JAWA BARAT 16516"	"Jl blbablaba, CINANGKA, DEPOK, JAWA BARAT 16516"	"Jakarta"	credit_card	pending	\N	https://app.midtrans.com/snap/v4/redirection/d042e5a3-083f-4cd4-9bb7-63d0c063d9f7	\N	\N	\N	Shipping: JNE REG - Layanan Reguler	"{\\"shipping_method\\":\\"JNE REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"JNE REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"25970\\",\\"label\\":\\"JAWA BARAT, DEPOK, CINANGKA, 16516\\",\\"postal_code\\":\\"16516\\",\\"full_address\\":\\"Jl blbablaba, CINANGKA, DEPOK, JAWA BARAT 16516\\"},\\"address_info\\":{\\"address_id\\":3,\\"label\\":\\"Rumah\\",\\"recipient_name\\":\\"Indra\\",\\"phone_recipient\\":\\"081287809468\\",\\"province_name\\":\\"JAWA BARAT\\",\\"city_name\\":\\"DEPOK\\",\\"subdistrict_name\\":\\"CINANGKA\\",\\"street_address\\":\\"Jl blbablaba\\",\\"saved_address_used\\":true,\\"address_saved\\":false,\\"set_as_primary\\":false},\\"customer_info\\":{\\"gender\\":\\"mens\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palaget\\",\\"birthdate\\":\\"2002-07-08\\",\\"newsletter_subscribe\\":false,\\"account_created\\":false,\\"existing_user\\":true},\\"voucher_info\\":{\\"voucher_code\\":\\"TANPA_KODE\\",\\"discount_amount\\":15000,\\"source\\":\\"form_data\\"},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout_with_address_integration_no_cod_no_tax_voucher_system\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/139.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-15T08:29:52.637474Z\\",\\"tax_rate\\":0,\\"cart_items_count\\":1,\\"total_weight\\":500,\\"subtotal_breakdown\\":{\\"items_subtotal\\":2799000,\\"shipping_cost\\":12000,\\"tax_amount\\":0,\\"discount_amount\\":15000,\\"total_amount\\":2796000}}}"	2025-08-15 15:29:52	2025-08-15 18:31:48	25970	JAWA BARAT, DEPOK, CINANGKA, 16516	16516	d042e5a3-083f-4cd4-9bb7-63d0c063d9f7	"{\\"va_numbers\\":[{\\"bank\\":\\"bca\\",\\"va_number\\":\\"33339158764893716661989\\"}],\\"transaction_time\\":\\"2025-08-15 15:30:45\\",\\"transaction_status\\":\\"expire\\",\\"transaction_id\\":\\"71c65d4c-9de4-4c56-9fa1-09a02762de65\\",\\"status_message\\":\\"Success, transaction is found\\",\\"status_code\\":\\"407\\",\\"signature_key\\":\\"53b5da1337784274295fcac9f303929c44f00f3fe90c84ddcc384ff91d64e862d272c8f370e39021296f01fb533f543afdf40175e0f3eb1c0dab41bf968ca537\\",\\"payment_type\\":\\"bank_transfer\\",\\"payment_amounts\\":[],\\"order_id\\":\\"SF-20250815-IW8HPF\\",\\"merchant_id\\":\\"G729994905\\",\\"gross_amount\\":\\"2796000.00\\",\\"fraud_status\\":\\"accept\\",\\"expiry_time\\":\\"2025-08-15 18:30:45\\",\\"currency\\":\\"IDR\\"}"	\N	\N	0	0.00
32	SF-20250818-H39GXU	2	Idam Palada	idampalada08@gmail.com	081287809468	cancelled	2799000.00	0.00	10000.00	0.00	2809000.00	IDR	"jl pejaten raya 33b, JATI PADANG, JAKARTA SELATAN, DKI JAKARTA 12540"	"jl pejaten raya 33b, JATI PADANG, JAKARTA SELATAN, DKI JAKARTA 12540"	"Jakarta"	credit_card	pending	\N	https://app.midtrans.com/snap/v4/redirection/8a5d5b1b-08cf-4354-8f0f-6be93f430522	\N	\N	\N	Shipping: JNE REG - Layanan Reguler	"{\\"shipping_method\\":\\"JNE REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17564\\",\\"label\\":\\"DKI JAKARTA, JAKARTA SELATAN, JATI PADANG, 12540\\",\\"postal_code\\":\\"12540\\",\\"full_address\\":\\"jl pejaten raya 33b, JATI PADANG, JAKARTA SELATAN, DKI JAKARTA 12540\\"},\\"address_info\\":{\\"address_id\\":4,\\"label\\":\\"Rumah\\",\\"recipient_name\\":\\"Faiz\\",\\"phone_recipient\\":\\"081287809468\\",\\"province_name\\":\\"DKI JAKARTA\\",\\"city_name\\":\\"JAKARTA SELATAN\\",\\"subdistrict_name\\":\\"JATI PADANG\\",\\"street_address\\":\\"jl pejaten raya 33b\\"},\\"customer_info\\":{\\"gender\\":\\"mens\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":\\"2002-07-08\\",\\"newsletter_subscribe\\":false},\\"voucher_info\\":null,\\"points_info\\":{\\"points_used\\":0,\\"points_discount\\":0,\\"user_points_balance_before\\":\\"0.00\\"},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout_with_points_support\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/139.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-18T04:37:51.457007Z\\",\\"tax_rate\\":0,\\"cart_items_count\\":1,\\"total_weight\\":500,\\"subtotal_breakdown\\":{\\"items_subtotal\\":2799000,\\"shipping_cost\\":10000,\\"tax_amount\\":0,\\"discount_amount\\":0,\\"points_discount\\":0,\\"total_amount\\":2809000}}}"	2025-08-18 11:37:51	2025-08-18 14:38:56	17564	DKI JAKARTA, JAKARTA SELATAN, JATI PADANG, 12540	12540	8a5d5b1b-08cf-4354-8f0f-6be93f430522	"{\\"va_numbers\\":[{\\"bank\\":\\"bca\\",\\"va_number\\":\\"33339657771073591028661\\"}],\\"transaction_time\\":\\"2025-08-18 11:37:54\\",\\"transaction_status\\":\\"expire\\",\\"transaction_id\\":\\"bb932da3-4410-4390-840e-98eb7878960e\\",\\"status_message\\":\\"Success, transaction is found\\",\\"status_code\\":\\"407\\",\\"signature_key\\":\\"1273c820938c61156cf703207ecc90457ee2af8bd5a1394275c2a1ce105f73b34eafaf6af4ee84401e0966424f723851fc40faa7bb60ce8c4301ab28558d716e\\",\\"payment_type\\":\\"bank_transfer\\",\\"payment_amounts\\":[],\\"order_id\\":\\"SF-20250818-H39GXU\\",\\"merchant_id\\":\\"G729994905\\",\\"gross_amount\\":\\"2809000.00\\",\\"fraud_status\\":\\"accept\\",\\"expiry_time\\":\\"2025-08-18 14:37:54\\",\\"currency\\":\\"IDR\\"}"	\N	\N	0	0.00
30	SF-20250815-ORCE8I	2	Idam Paladaa	idampalada08@gmail.com	081287809468	cancelled	2999000.00	0.00	10000.00	0.00	3009000.00	IDR	"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110"	"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110"	"Jakarta"	credit_card	pending	\N	https://app.midtrans.com/snap/v4/redirection/943a96b9-28b2-449d-8229-4f28aaef3165	\N	\N	\N	Shipping: JNE REG - Layanan Reguler	"{\\"shipping_method\\":\\"JNE REG - Layanan Reguler\\",\\"shipping_method_detail\\":\\"JNE REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17544\\",\\"label\\":\\"DKI JAKARTA, JAKARTA SELATAN, SELONG, 12110\\",\\"postal_code\\":\\"12110\\",\\"full_address\\":\\"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110\\"},\\"address_info\\":{\\"address_id\\":2,\\"label\\":\\"Rumah\\",\\"recipient_name\\":\\"Idam Palada\\",\\"phone_recipient\\":\\"081287809468\\",\\"province_name\\":\\"DKI JAKARTA\\",\\"city_name\\":\\"JAKARTA SELATAN\\",\\"subdistrict_name\\":\\"SELONG\\",\\"street_address\\":\\"Jl Pattimura 20, Kementerian pekerjaan umum\\",\\"saved_address_used\\":true,\\"address_saved\\":false,\\"set_as_primary\\":false},\\"customer_info\\":{\\"gender\\":\\"mens\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Paladaa\\",\\"birthdate\\":\\"2002-07-08\\",\\"newsletter_subscribe\\":false,\\"account_created\\":false,\\"existing_user\\":true},\\"voucher_info\\":null,\\"checkout_info\\":{\\"created_via\\":\\"web_checkout_with_address_integration_no_cod_no_tax_voucher_system\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/139.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-15T15:12:51.081004Z\\",\\"tax_rate\\":0,\\"cart_items_count\\":1,\\"total_weight\\":500,\\"subtotal_breakdown\\":{\\"items_subtotal\\":2999000,\\"shipping_cost\\":10000,\\"tax_amount\\":0,\\"discount_amount\\":0,\\"total_amount\\":3009000}}}"	2025-08-15 22:12:51	2025-08-16 01:13:55	17544	DKI JAKARTA, JAKARTA SELATAN, SELONG, 12110	12110	943a96b9-28b2-449d-8229-4f28aaef3165	"{\\"va_numbers\\":[{\\"bank\\":\\"bca\\",\\"va_number\\":\\"33339140955077768834107\\"}],\\"transaction_time\\":\\"2025-08-15 22:12:53\\",\\"transaction_status\\":\\"expire\\",\\"transaction_id\\":\\"05e91355-16a3-4124-8d24-85fb0e631d1f\\",\\"status_message\\":\\"Success, transaction is found\\",\\"status_code\\":\\"407\\",\\"signature_key\\":\\"e672c5410f0a6974bfef40db6e76b5e6ff42d6aed395784667c3183025aa50a14ca0e101c8dfa33d3e9da676fc559a860e31fe3acfdd0783fa073182f886fde9\\",\\"payment_type\\":\\"bank_transfer\\",\\"payment_amounts\\":[],\\"order_id\\":\\"SF-20250815-ORCE8I\\",\\"merchant_id\\":\\"G729994905\\",\\"gross_amount\\":\\"3009000.00\\",\\"fraud_status\\":\\"accept\\",\\"expiry_time\\":\\"2025-08-16 01:12:53\\",\\"currency\\":\\"IDR\\"}"	\N	\N	0	0.00
31	SF-20250817-NNWUQZ	2	Idam Palada	idampalada08@gmail.com	081287809468	cancelled	1049000.00	0.00	10000.00	0.00	1059000.00	IDR	"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110"	"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110"	"Jakarta"	credit_card	pending	\N	https://app.midtrans.com/snap/v4/redirection/0e69534a-505e-4adf-9b23-b3041b056f5c	\N	\N	\N	Shipping: JNE REG - Layanan Reguler	"{\\"shipping_method\\":\\"JNE REG - Layanan Reguler\\",\\"destination_info\\":{\\"id\\":\\"17544\\",\\"label\\":\\"DKI JAKARTA, JAKARTA SELATAN, SELONG, 12110\\",\\"postal_code\\":\\"12110\\",\\"full_address\\":\\"Jl Pattimura 20, Kementerian pekerjaan umum, SELONG, JAKARTA SELATAN, DKI JAKARTA 12110\\"},\\"address_info\\":{\\"address_id\\":2,\\"label\\":\\"Rumah\\",\\"recipient_name\\":\\"Idam Palada\\",\\"phone_recipient\\":\\"081287809468\\",\\"province_name\\":\\"DKI JAKARTA\\",\\"city_name\\":\\"JAKARTA SELATAN\\",\\"subdistrict_name\\":\\"SELONG\\",\\"street_address\\":\\"Jl Pattimura 20, Kementerian pekerjaan umum\\"},\\"customer_info\\":{\\"gender\\":\\"mens\\",\\"first_name\\":\\"Idam\\",\\"last_name\\":\\"Palada\\",\\"birthdate\\":\\"2002-07-08\\",\\"newsletter_subscribe\\":false},\\"voucher_info\\":null,\\"points_info\\":{\\"points_used\\":0,\\"points_discount\\":0,\\"user_points_balance_before\\":\\"0.00\\"},\\"checkout_info\\":{\\"created_via\\":\\"web_checkout_with_points_support\\",\\"user_agent\\":\\"Mozilla\\\\\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\\\\\/537.36 (KHTML, like Gecko) Chrome\\\\\\/139.0.0.0 Safari\\\\\\/537.36\\",\\"ip_address\\":\\"127.0.0.1\\",\\"checkout_timestamp\\":\\"2025-08-17T07:46:03.937278Z\\",\\"tax_rate\\":0,\\"cart_items_count\\":1,\\"total_weight\\":500,\\"subtotal_breakdown\\":{\\"items_subtotal\\":1049000,\\"shipping_cost\\":10000,\\"tax_amount\\":0,\\"discount_amount\\":0,\\"points_discount\\":0,\\"total_amount\\":1059000}}}"	2025-08-17 14:46:03	2025-08-17 17:49:07	17544	DKI JAKARTA, JAKARTA SELATAN, SELONG, 12110	12110	0e69534a-505e-4adf-9b23-b3041b056f5c	"{\\"va_numbers\\":[{\\"bank\\":\\"bca\\",\\"va_number\\":\\"33339855614984191916008\\"}],\\"transaction_time\\":\\"2025-08-17 14:48:05\\",\\"transaction_status\\":\\"expire\\",\\"transaction_id\\":\\"0a9edf5f-2a31-4b84-a649-0b75413daa7a\\",\\"status_message\\":\\"Success, transaction is found\\",\\"status_code\\":\\"407\\",\\"signature_key\\":\\"3ad5048e6517533dfd3d402b4b44a08c7a5cdddcb9662bd91088e9338577475afb34c677dedc6269b3000d42dcbb054345959b7f17cdd7acba1cdf0bf8fd11e9\\",\\"payment_type\\":\\"bank_transfer\\",\\"payment_amounts\\":[],\\"order_id\\":\\"SF-20250817-NNWUQZ\\",\\"merchant_id\\":\\"G729994905\\",\\"gross_amount\\":\\"1059000.00\\",\\"fraud_status\\":\\"accept\\",\\"expiry_time\\":\\"2025-08-17 17:48:05\\",\\"currency\\":\\"IDR\\"}"	\N	\N	0	0.00
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: points_transactions; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.points_transactions (id, user_id, order_id, type, amount, description, reference, balance_before, balance_after, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.products (id, name, slug, short_description, description, category_id, brand, sku, gender_target, product_type, price, sale_price, stock_quantity, min_stock_level, weight, images, features, specifications, available_sizes, available_colors, is_active, is_featured, is_featured_sale, published_at, sale_start_date, sale_end_date, search_keywords, meta_title, meta_description, meta_keywords, dimensions, meta_data, created_at, updated_at, sku_parent, length, width, height, ginee_last_sync, ginee_sync_status, ginee_data, ginee_id, ginee_product_id, ginee_sync_error, warehouse_stock, ginee_last_stock_sync, ginee_last_stock_push) FROM stdin;
79	Doube Box - Size 30	doube-box-size-30	Doube Box	-	13	NIKE	BOX	["mens"]	apparel	5000.00	1000.00	1307	5	500.00	["https:\\/\\/down-id.img.susercontent.com\\/file\\/id-11134207-7rbk1-mam5hrv8qy3ie9@resize_w900_nl.webp"]	\N	\N	["30"]	\N	t	f	f	2025-08-19 10:22:07	\N	\N	\N	\N	\N	\N	\N	\N	2025-08-19 00:12:29	2025-08-19 11:51:23	ABC123	\N	\N	\N	\N	pending	\N	\N	\N	\N	0	2025-08-19 11:51:23	\N
81	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301-42 - Size 20	sepatu-lari-pria-asics-gel-nimbus-26-green-11b794301-42-size-20	Sepatu Lari Pria ASICS Gel Nimbus 26 Green - 11B794301-42	-	13	ADIDAS	11B794301/42	["mens"]	apparel	10000.00	9000.00	1	5	500.00	["https:\\/\\/images.tokopedia.net\\/img\\/cache\\/700\\/aphluv\\/1997\\/1\\/1\\/932463d01aff4b26b277410a75b6dfba~.jpeg"]	\N	\N	["20"]	\N	t	f	f	2025-08-19 10:22:07	\N	\N	\N	\N	\N	\N	\N	\N	2025-08-19 08:32:51	2025-08-19 11:51:24	ABCS123	\N	\N	\N	\N	pending	\N	\N	\N	\N	0	2025-08-19 11:51:24	\N
82	Sepatu Sneakers Pria NEW BALANCE 237 GREY - MS237MCN-42	sepatu-sneakers-pria-new-balance-237-grey-ms237mcn-42	Sepatu Sneakers Pria NEW BALANCE 237 GREY - MS237MCN-42		13	Unknown	MS237MCN/42	["mens"]	apparel	1000000.00	0.00	11	5	500.00	["https:\\/\\/p16-oec-sg.ibyteimg.com\\/tos-alisg-i-aphluv4xwc-sg\\/cc74378b899741a5b32dc43c23243c64~tplv-aphluv4xwc-origin-jpeg.jpeg?dr=15568&from=520841845&idc=my&ps=933b5bde&shcp=2c1af732&shp=1f0b6a75&t=555f072d"]	\N	\N	["42"]	\N	t	f	f	2025-08-19 10:22:07	\N	\N	\N	\N	\N	\N	\N	\N	2025-08-19 08:36:15	2025-08-19 11:51:25	ABDD324	\N	\N	\N	\N	pending	\N	\N	\N	\N	0	2025-08-19 11:51:25	\N
83	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB-44 - Size 43	sepatu-sneakers-pria-new-balance-550-concrete-grey-bb550mcb-44-size-43	Sepatu Sneakers Pria NEW BALANCE 550 CONCRETE GREY - BB550MCB-44		13	NIKE	197375689975	["mens"]	apparel	1000000.00	0.00	7	5	500.00	["https:\\/\\/p16-oec-sg.ibyteimg.com\\/tos-alisg-i-aphluv4xwc-sg\\/c1b04922dbe243b18affcf5eaeb89bc2~tplv-aphluv4xwc-origin-jpeg.jpeg?dr=15568&from=520841845&idc=my2&ps=933b5bde&shcp=2c1af732&shp=1f0b6a75&t=555f072d"]	\N	\N	["43"]	\N	t	f	f	2025-08-19 10:22:07	\N	\N	\N	\N	\N	\N	\N	\N	2025-08-19 08:39:07	2025-08-19 11:51:26	abh832	\N	\N	\N	\N	pending	\N	\N	\N	\N	0	2025-08-19 11:51:26	\N
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
WBYcF2co0W2RNTLFVc4o7AeidewZnlp9DV8H3Mn2	\N	127.0.0.1	Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1	YTozOntzOjY6Il90b2tlbiI7czo0MDoiYmxwY0VrZjVDRE4xZG1HOXlETmpwbTVBMWUyMUZNTEJWZE52ZXpwNyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NzQ6Imh0dHBzOi8vc25lYWtlci5tZWx0ZWRjbG91ZC5jbG91ZC9wcm9kdWN0cy9uaWtlLWJhZG1pbnRvbi1hYmNkZTEyMy1zaXplLTQxIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==	1755576271
2xj8ohEcx4Ns1tXMFIXzon18JEJZd89QD8XQpQDb	1	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36	YTo4OntzOjY6Il90b2tlbiI7czo0MDoiYVVjT1pMVTZwVEhvU0pNRnJweUt4MkpZelgyYUpZWWJsVWh1czJRZyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjQ4OiJodHRwczovL3NuZWFrZXIubWVsdGVkY2xvdWQuY2xvdWQvYWRtaW4vcHJvZHVjdHMiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjYwOiIkMnkkMTIkRTltWkZLTHJWV3pWL0suZ0Q2bU5SZUQuWkNnSmF2Njk2S2NhMWVBaDBEeVNxQXhhTXZsZEsiO3M6ODoiZmlsYW1lbnQiO2E6MDp7fXM6NjoidGFibGVzIjthOjM6e3M6NDA6IjY2NmU1MGJjMGJlYjYxM2VmYTg5YjlhZjRjZWRhZmQyX2ZpbHRlcnMiO2E6OTp7czoxMjoidm91Y2hlcl90eXBlIjthOjE6e3M6NToidmFsdWUiO047fXM6MTc6ImNhdGVnb3J5X2N1c3RvbWVyIjthOjE6e3M6NToidmFsdWUiO047fXM6MTI6ImNvZGVfcHJvZHVjdCI7YToxOntzOjU6InZhbHVlIjtOO31zOjE1OiJhY3RpdmVfdm91Y2hlcnMiO2E6MTp7czo4OiJpc0FjdGl2ZSI7YjowO31zOjEzOiJleHBpcmluZ19zb29uIjthOjE6e3M6ODoiaXNBY3RpdmUiO2I6MDt9czo5OiJsb3dfcXVvdGEiO2E6MTp7czo4OiJpc0FjdGl2ZSI7YjowO31zOjEwOiJuZXZlcl91c2VkIjthOjE6e3M6ODoiaXNBY3RpdmUiO2I6MDt9czoxMjoiY3JlYXRlZF9kYXRlIjthOjI6e3M6MTI6ImNyZWF0ZWRfZnJvbSI7TjtzOjEzOiJjcmVhdGVkX3VudGlsIjtOO31zOjExOiJzeW5jX3N0YXR1cyI7YToxOntzOjU6InZhbHVlIjtOO319czozOToiNjY2ZTUwYmMwYmViNjEzZWZhODliOWFmNGNlZGFmZDJfc2VhcmNoIjtzOjA6IiI7czozNzoiNjY2ZTUwYmMwYmViNjEzZWZhODliOWFmNGNlZGFmZDJfc29ydCI7YToyOntzOjY6ImNvbHVtbiI7TjtzOjk6ImRpcmVjdGlvbiI7Tjt9fX0=	1755578613
6cIururwUTHwbDR0apPKCzYAdPMSLxGNTYjACTk5	1	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36	YTo4OntzOjY6Il90b2tlbiI7czo0MDoiY3RmWW1DOTR3cTFHeVhUcjJ0R1c5NVJYU051Vk51V0dGdnJqdkcxTiI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czozOiJ1cmwiO2E6MDp7fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjE7czoxNzoicGFzc3dvcmRfaGFzaF93ZWIiO3M6NjA6IiQyeSQxMiRFOW1aRktMclZXelYvSy5nRDZtTlJlRC5aQ2dKYXY2OTZLY2ExZUFoMER5U3FBeGFNdmxkSyI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTU6Imh0dHBzOi8vc25lYWtlci5tZWx0ZWRjbG91ZC5jbG91ZC9hZG1pbi9naW5lZS1zeW5jLWxvZ3MiO31zOjY6InRhYmxlcyI7YTozOntzOjQwOiI2NjZlNTBiYzBiZWI2MTNlZmE4OWI5YWY0Y2VkYWZkMl9maWx0ZXJzIjthOjk6e3M6MTI6InZvdWNoZXJfdHlwZSI7YToxOntzOjU6InZhbHVlIjtOO31zOjE3OiJjYXRlZ29yeV9jdXN0b21lciI7YToxOntzOjU6InZhbHVlIjtOO31zOjEyOiJjb2RlX3Byb2R1Y3QiO2E6MTp7czo1OiJ2YWx1ZSI7Tjt9czoxNToiYWN0aXZlX3ZvdWNoZXJzIjthOjE6e3M6ODoiaXNBY3RpdmUiO2I6MDt9czoxMzoiZXhwaXJpbmdfc29vbiI7YToxOntzOjg6ImlzQWN0aXZlIjtiOjA7fXM6OToibG93X3F1b3RhIjthOjE6e3M6ODoiaXNBY3RpdmUiO2I6MDt9czoxMDoibmV2ZXJfdXNlZCI7YToxOntzOjg6ImlzQWN0aXZlIjtiOjA7fXM6MTI6ImNyZWF0ZWRfZGF0ZSI7YToyOntzOjEyOiJjcmVhdGVkX2Zyb20iO047czoxMzoiY3JlYXRlZF91bnRpbCI7Tjt9czoxMToic3luY19zdGF0dXMiO2E6MTp7czo1OiJ2YWx1ZSI7Tjt9fXM6Mzk6IjY2NmU1MGJjMGJlYjYxM2VmYTg5YjlhZjRjZWRhZmQyX3NlYXJjaCI7czowOiIiO3M6Mzc6IjY2NmU1MGJjMGJlYjYxM2VmYTg5YjlhZjRjZWRhZmQyX3NvcnQiO2E6Mjp7czo2OiJjb2x1bW4iO047czo5OiJkaXJlY3Rpb24iO047fX1zOjg6ImZpbGFtZW50IjthOjA6e319	1755586354
\.


--
-- Data for Name: shopping_cart; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.shopping_cart (id, user_id, session_id, product_id, quantity, product_options, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: user_addresses; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.user_addresses (id, user_id, label, recipient_name, phone_recipient, province_name, city_name, subdistrict_name, postal_code, destination_id, street_address, notes, is_primary, is_active, created_at, updated_at) FROM stdin;
2	2	Rumah	Idam Palada	081287809468	DKI JAKARTA	JAKARTA SELATAN	SELONG	12110	17544	Jl Pattimura 20, Kementerian pekerjaan umum	\N	f	t	2025-08-09 17:38:13	2025-08-18 11:40:00
4	2	Rumah	Faiz	081287809468	DKI JAKARTA	JAKARTA SELATAN	JATI PADANG	12540	17564	jl pejaten raya 33b	\N	f	t	2025-08-18 11:37:51	2025-08-18 11:40:00
3	2	Rumah	Indra	081287809468	JAWA BARAT	DEPOK	CINANGKA	16516	25970	Jl blbablaba	\N	f	t	2025-08-12 13:48:57	2025-08-18 11:40:00
1	2	Rumah	Idam	081287809468	DKI JAKARTA	JAKARTA SELATAN	PONDOK PINANG	12310	17551	Jalan Bank exim no 37 Rt  Rw 1	\N	t	t	2025-08-09 17:37:31	2025-08-18 11:40:00
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.users (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, google_id, avatar, total_spent, total_orders, spending_updated_at, customer_tier, phone, gender, birthdate, zodiac, spending_6_months, tier_period_start, last_tier_evaluation, points_balance, total_points_earned, total_points_redeemed) FROM stdin;
7	Jingga Aulia	jinggaaulia330@gmail.com	2025-08-11 18:57:24	\N	E7DWENgQSjoWRfsVT0oUtydFU6dF85WE5E4DCPHhzgySpjvGPdSIrOGllTRX	2025-08-11 18:57:24	2025-08-11 18:57:24	109393348447685104197	https://lh3.googleusercontent.com/a/ACg8ocJMp674dkAHPM2t_RKGjigbR-ilNN4k4zQ-VAwVK2Yr01mobw=s96-c	0.00	0	\N	basic	\N	\N	\N	\N	0.00	2025-08-11 18:57:24	\N	0.00	0.00	0.00
4	alas kaki	rezaapreza7@gmail.com	2025-08-02 18:14:52	\N	83gOVI0RH7Oqxushj5F4GBUczOWiLmnE3LiLxdHWh7sQA5eD0RyjXwdvE4pp	2025-08-02 18:14:52	2025-08-02 18:14:52	100917949223713832372	https://lh3.googleusercontent.com/a/ACg8ocIYvxqX9NbGFzV4H5nMEPbE4Wju0ZoRtwjAgRih6NTi3KtSWg=s96-c	0.00	0	\N	basic	\N	\N	\N	\N	0.00	2025-08-02 18:14:52	\N	0.00	0.00	0.00
5	jingga aulia	auliajingga84@gmail.com	2025-08-05 02:24:50	\N	13ikztugwMUV4ZNMNjc5xzXcsUU8khGp6dnk3kIIPNQZdDTbLWvKbXSkGEVk	2025-08-05 02:24:50	2025-08-05 02:24:50	102181209357955976938	https://lh3.googleusercontent.com/a/ACg8ocKGxUU6Sg4gWXCM-Ns3pnIOm6wnZ6t2RGt6y2VwehCxetfb6g=s96-c	0.00	0	\N	basic	\N	\N	\N	\N	0.00	2025-08-05 02:24:50	\N	0.00	0.00	0.00
6	Koplak	bagussimdigei3@gmail.com	2025-08-05 09:46:15	\N	x7YeFbkuTW0QJLpM10s1ssAjJGJJFms2Ua6PtckEmPeM0vjyOWC9b8GFP6Ol	2025-08-05 09:40:12	2025-08-05 09:46:15	110433458851052157100	https://lh3.googleusercontent.com/a/ACg8ocKqIAXbUgjkW_Q_tmzfdFK_Kd0R--udq-aDeF0gne_0GI8GvHY=s96-c	0.00	0	\N	basic	\N	\N	\N	\N	0.00	2025-08-05 09:40:12	\N	0.00	0.00	0.00
3	Lukman Gran	granlukman@gmail.com	2025-08-08 22:44:15	\N	3zBOBOf5A6751zig50jlvwGKfoXfyPPUPy5vGgqS1u7kA9ua9AbBZwTT9RKo	2025-08-02 04:37:07	2025-08-08 22:44:15	114548091524304854295	https://lh3.googleusercontent.com/a/ACg8ocIo_NmWaL7mc7Lo58ZBh9ZYfhPVcWKGfGIFLGQ4Qt-LMv5CPg=s96-c	1452000.00	1	2025-08-07 13:51:27	advance	\N	\N	\N	\N	0.00	2025-08-02 04:37:07	\N	0.00	0.00	0.00
1	Admin	admin@sneakerflash.com	2025-08-02 03:53:40	$2y$12$E9mZFKLrVWzV/K.gD6mNReD.ZCgJav696Kca1eAh0DySqAxaMvldK	3MeLgCI8ZLIYI8FmPIcRjDfq5NHlBQV27HXXVMcdMFHHmcCezaYMbpH9Zgx5	2025-08-02 03:53:40	2025-08-02 03:53:40	\N	\N	0.00	0	\N	basic	\N	\N	\N	\N	0.00	2025-08-02 03:53:40	\N	0.00	0.00	0.00
2	Idam	idampalada08@gmail.com	2025-08-18 11:33:46	$2y$12$DpWMpG7y0/QXeLb70VUYRuBAhp4f/8IFlSwUdSNh9BzbECbddXUTe	7xuMh6S2WsGjUzfnCsLacbXmWCb6VkXBkNOS5gQ9aBNodEEwbEFQAeVGR1U3	2025-08-02 03:58:15	2025-08-18 11:37:51	109979709869434697051	https://lh3.googleusercontent.com/a/ACg8ocLrp4rrwbqm-0ck9MPUuo0y1KuSeMsGeM729VPhYcNuiinm1w=s96-c	100680.00	6	2025-08-18 11:37:51	basic	081287809468	mens	2002-07-08	CANCER	100680.00	2025-08-02 03:58:15	2025-08-18 11:37:51	0.00	0.00	0.00
\.


--
-- Data for Name: voucher_sync_log; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.voucher_sync_log (id, sync_type, status, records_processed, errors_count, error_details, synced_at, execution_time_ms, created_at, updated_at) FROM stdin;
9fa3824d-ed92-451e-9b75-bf4987212f03	spreadsheet_to_db_force_new	success	5	0	\N	2025-08-15 15:22:26	2095	2025-08-15 15:22:26	2025-08-15 15:22:28
\.


--
-- Data for Name: voucher_usage; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.voucher_usage (id, voucher_id, customer_id, customer_email, order_id, discount_amount, order_total, used_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: vouchers; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.vouchers (id, created_at, updated_at, sync_status, spreadsheet_row_id, code_product, voucher_code, name_voucher, start_date, end_date, min_purchase, quota, claim_per_customer, voucher_type, value, discount_max, category_customer, is_active, total_used) FROM stdin;
9fa38251-1d05-46b6-8ac3-5286180fb315	2025-08-15 15:22:28	2025-08-15 15:22:28	synced	2	All product	SNEAK123	merdeka1	2025-09-14 15:22:28	2025-09-14 15:22:28	50000.00	100	1	NOMINAL	Rp50.000	50000.00	all customer	t	0
9fa38251-1dd9-4a0c-b479-86554bf5abf2	2025-08-15 15:22:28	2025-08-15 15:22:28	synced	3	All product	SNEAK5%	merdeka2	2025-09-14 15:22:28	2025-09-14 15:22:28	50000.00	100	1	PERCENT	5%	50000.00	ultimate	t	0
9fa38251-1e4e-43f2-b942-fdfca9bfa6db	2025-08-15 15:22:28	2025-08-15 15:22:28	synced	4	All product	SNEAK3%	merdeka3	2025-09-14 15:22:28	2025-09-14 15:22:28	50000.00	100	1	PERCENT	3%	100000.00	basic	t	0
9fa38251-1ed0-4b95-9d62-5f2dadf9748d	2025-08-15 15:22:28	2025-08-15 15:22:28	synced	5	All product	SNEAK7%	merdeka4	2025-09-14 15:22:28	2025-09-14 15:22:28	50000.00	100	1	PERCENT	7%	150000.00	advance	t	0
9fa38251-1f5c-427d-a568-d7c7a9b61ad4	2025-08-15 15:22:28	2025-08-15 15:28:47	pending	6	All product	TANPA_KODE	merdeka5	2025-08-01 15:22:28	2025-09-14 15:22:28	0.00	100	1	PERCENT	30%	15000.00	all customer	t	0
\.


--
-- Data for Name: webhook_events; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.webhook_events (id, event_id, source, entity, action, payload, processed_at, created_at, updated_at, event_type, ip_address, user_agent, headers, processed, processing_result, retry_count) FROM stdin;
1	TEST-STAG-1	ginee	order	CREATE	{"id": "TEST-STAG-1", "action": "CREATE", "entity": "order", "payload": {"orderId": "O-1"}}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
2	TEST-GLOBAL-1	ginee	ping	CHECK	{"id": "TEST-GLOBAL-1", "action": "CHECK", "entity": "ping"}	\N	2025-08-18 14:19:05	\N	\N	\N	\N	\N	f	\N	0
3	GENIE68A2D3FCCFF47E0001650C1D	ginee	order	UPDATE	{"id": "GENIE68A2D3FCCFF47E0001650C1D", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T07:19:22Z", "shopId": "SH625922A0E21B84000104B3E9", "channel": "TIKTOK_ID", "orderId": "SO68A2D39446E0FB00019B05BB", "createAt": "2025-08-18T07:17:38Z", "deleteAt": null, "orderStatus": "PAID", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T07:19:24.498314Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
4	TEST-ORD-1	ginee	order	CREATE	{"id": "TEST-ORD-1", "action": "CREATE", "entity": "order", "payload": {"orderId": "O-123"}}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
5	GENIE68A2D48BE21B8400018AAA18	ginee	order	UPDATE	{"id": "GENIE68A2D48BE21B8400018AAA18", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-07T17:25:04Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO6894E1054CEDFD0001CCF5BA", "createAt": "2025-08-07T17:22:21Z", "deleteAt": null, "orderStatus": "SHIPPING", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T07:21:47.694099Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
6	GENIE68A2D5F84CEDFD0001A786E4	ginee	order	CREATE	{"id": "GENIE68A2D5F84CEDFD0001A786E4", "action": "CREATE", "entity": "order", "payload": {"payAt": null, "shopId": "SH625922A0E21B84000104B3E9", "channel": "TIKTOK_ID", "orderId": "SO68A2D5F8C9E77C00015D72A8", "createAt": "2025-08-18T07:27:50Z", "deleteAt": null, "orderStatus": "PENDING_PAYMENT", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T07:27:52.128789Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
7	GENIE68A2D6284CEDFD00017AB81C	ginee	order	UPDATE	{"id": "GENIE68A2D6284CEDFD00017AB81C", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T07:28:37Z", "shopId": "SH625922A0E21B84000104B3E9", "channel": "TIKTOK_ID", "orderId": "SO68A2D5F8C9E77C00015D72A8", "createAt": "2025-08-18T07:27:50Z", "deleteAt": null, "orderStatus": "PAID", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T07:28:40.112444Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
8	GENIE68A2D784C9E77C00018E3EA6	ginee	order	UPDATE	{"id": "GENIE68A2D784C9E77C00018E3EA6", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T07:04:55Z", "shopId": "SH659BCDE4590801000181E93B", "channel": "SHOPEE_ID", "orderId": "SO68A2D093C9E77C0001CE5993", "createAt": "2025-08-18T07:04:50Z", "deleteAt": null, "orderStatus": "READY_TO_SHIP", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T07:34:28.588650Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
9	GENIE68A2D8244CEDFD0001A79AC1	ginee	order	CREATE	{"id": "GENIE68A2D8244CEDFD0001A79AC1", "action": "CREATE", "entity": "order", "payload": {"payAt": null, "shopId": "SH659BCDE4590801000181E93B", "channel": "SHOPEE_ID", "orderId": "SO68A2D82446E0FB00013DFA0C", "createAt": "2025-08-18T07:37:07Z", "deleteAt": null, "orderStatus": "PENDING_PAYMENT", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T07:37:08.090778Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
10	GENIE68A2D840E21B8400018ACDEC	ginee	order	UPDATE	{"id": "GENIE68A2D840E21B8400018ACDEC", "action": "UPDATE", "entity": "order", "payload": {"payAt": null, "shopId": "SH659BCDE4590801000181E93B", "channel": "SHOPEE_ID", "orderId": "SO68A2D82446E0FB00013DFA0C", "createAt": "2025-08-18T07:37:07Z", "deleteAt": null, "orderStatus": "CANCELLED", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T07:37:36.877224Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
11	GENIE68A2D860C9E77C00018E4638	ginee	order	UPDATE	{"id": "GENIE68A2D860C9E77C00018E4638", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-14T16:01:31Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO689E0837E828860001F3972A", "createAt": "2025-08-14T16:00:51Z", "deleteAt": null, "orderStatus": "SHIPPING", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T07:38:08.483630Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
12	GENIE68A2DB41E21B8400018AEA44	ginee	order	CREATE	{"id": "GENIE68A2DB41E21B8400018AEA44", "action": "CREATE", "entity": "order", "payload": {"payAt": null, "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2DB414CEDFD00017BC9F4", "createAt": "2025-08-18T07:50:19Z", "deleteAt": null, "orderStatus": "PENDING_PAYMENT", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T07:50:25.155363Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
13	GENIE68A2DC204CEDFD00017AF0EE	ginee	order	CREATE	{"id": "GENIE68A2DC204CEDFD00017AF0EE", "action": "CREATE", "entity": "order", "payload": {"payAt": null, "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2DC20C9E77C0001D01B86", "createAt": "2025-08-18T07:54:07Z", "deleteAt": null, "orderStatus": "PENDING_PAYMENT", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T07:54:08.145118Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
14	GENIE68A2DC2BC9E77C00018E6D17	ginee	order	UPDATE	{"id": "GENIE68A2DC2BC9E77C00018E6D17", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T07:54:18Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2DC20C9E77C0001D01B86", "createAt": "2025-08-18T07:54:07Z", "deleteAt": null, "orderStatus": "PAID", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T07:54:19.016915Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
15	GENIE68A2DD79E21B8400018B00C4	ginee	order	CREATE	{"id": "GENIE68A2DD79E21B8400018B00C4", "action": "CREATE", "entity": "order", "payload": {"payAt": null, "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2DD79E8288600017ACE2A", "createAt": "2025-08-18T07:59:51Z", "deleteAt": null, "orderStatus": "PENDING_PAYMENT", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T07:59:53.143954Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
16	GENIE68A2DE704CEDFD00017B1107	ginee	order	UPDATE	{"id": "GENIE68A2DE704CEDFD00017B1107", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-12T12:51:42Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO689B37F5E8288600019BC9B7", "createAt": "2025-08-12T12:47:48Z", "deleteAt": null, "orderStatus": "SHIPPING", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:04:00.606439Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
17	GENIE68A2DED6C9E77C00018E96EF	ginee	order	UPDATE	{"id": "GENIE68A2DED6C9E77C00018E96EF", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T08:05:39Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2CCC646E0FB000199C5E0", "createAt": "2025-08-18T06:48:37Z", "deleteAt": null, "orderStatus": "PAID", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:05:42.820101Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
18	GENIE68A2DF724CEDFD00017B28C8	ginee	order	UPDATE	{"id": "GENIE68A2DF724CEDFD00017B28C8", "action": "UPDATE", "entity": "order", "payload": {"payAt": null, "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2DB414CEDFD00017BC9F4", "createAt": "2025-08-18T07:50:19Z", "deleteAt": null, "orderStatus": "CANCELLED", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:08:18.750201Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
19	GENIE68A2E1FF4CEDFD00017B507F	ginee	order	CREATE	{"id": "GENIE68A2E1FF4CEDFD00017B507F", "action": "CREATE", "entity": "order", "payload": {"payAt": null, "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2E1FFE8288600017B726C", "createAt": "2025-08-18T08:19:10Z", "deleteAt": null, "orderStatus": "PENDING_PAYMENT", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:19:11.471725Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
20	GENIE68A2E251E21B8400018B5760	ginee	order	UPDATE	{"id": "GENIE68A2E251E21B8400018B5760", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T08:05:39Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2CCC646E0FB000199C5E0", "createAt": "2025-08-18T06:48:37Z", "deleteAt": null, "orderStatus": "READY_TO_SHIP", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:20:33.233566Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
21	GENIE68A2E25ACFF47E000165BEAF	ginee	order	UPDATE	{"id": "GENIE68A2E25ACFF47E000165BEAF", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-07T17:25:04Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO6894E1054CEDFD0001CCF5BA", "createAt": "2025-08-07T17:22:21Z", "deleteAt": null, "orderStatus": "DELIVERED", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:20:42.114279Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
22	GENIE68A2E2874CEDFD00017B5746	ginee	order	CREATE	{"id": "GENIE68A2E2874CEDFD00017B5746", "action": "CREATE", "entity": "order", "payload": {"payAt": null, "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2E28752FAFF000192D4B6", "createAt": "2025-08-18T08:21:24Z", "deleteAt": null, "orderStatus": "PENDING_PAYMENT", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:21:27.166683Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
23	GENIE68A2E28BC9E77C00018ED325	ginee	order	UPDATE	{"id": "GENIE68A2E28BC9E77C00018ED325", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T08:21:30Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2E28752FAFF000192D4B6", "createAt": "2025-08-18T08:21:24Z", "deleteAt": null, "orderStatus": "PAID", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:21:31.384909Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
24	GENIE68A2E2A8C9E77C00018ED446	ginee	order	UPDATE	{"id": "GENIE68A2E2A8C9E77C00018ED446", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T07:04:55Z", "shopId": "SH659BCDE4590801000181E93B", "channel": "SHOPEE_ID", "orderId": "SO68A2D093C9E77C0001CE5993", "createAt": "2025-08-18T07:04:50Z", "deleteAt": null, "orderStatus": "SHIPPING", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:22:00.485886Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
25	GENIE68A2E327E21B8400018B6510	ginee	order	UPDATE	{"id": "GENIE68A2E327E21B8400018B6510", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T07:54:18Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2DC20C9E77C0001D01B86", "createAt": "2025-08-18T07:54:07Z", "deleteAt": null, "orderStatus": "READY_TO_SHIP", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:24:07.205054Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
26	GENIE68A2E32ECFF47E000165CD64	ginee	order	CREATE	{"id": "GENIE68A2E32ECFF47E000165CD64", "action": "CREATE", "entity": "order", "payload": {"payAt": null, "shopId": "SH624FF7B8E21B840001A2D166", "channel": "BLIBLI_ID", "orderId": "SO68A2E32E4CEDFD00017CE7D7", "createAt": "2025-08-18T08:24:14Z", "deleteAt": null, "orderStatus": "PENDING_PAYMENT", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:24:14.448203Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
27	GENIE68A2E3414CEDFD0001A8354B	ginee	order	UPDATE	{"id": "GENIE68A2E3414CEDFD0001A8354B", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T08:24:30.745Z", "shopId": "SH624FF7B8E21B840001A2D166", "channel": "BLIBLI_ID", "orderId": "SO68A2E32E4CEDFD00017CE7D7", "createAt": "2025-08-18T08:24:31Z", "deleteAt": null, "orderStatus": "PAID", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:24:33.973721Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
28	GENIE68A2E4B2C9E77C00018EF2EB	ginee	order	CREATE	{"id": "GENIE68A2E4B2C9E77C00018EF2EB", "action": "CREATE", "entity": "order", "payload": {"payAt": null, "shopId": "SH625922A0E21B84000104B3E9", "channel": "TIKTOK_ID", "orderId": "SO68A2E4B252FAFF00019324B7", "createAt": "2025-08-18T08:30:41Z", "deleteAt": null, "orderStatus": "PENDING_PAYMENT", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:30:42.886451Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
29	GENIE68A2E4BAC9E77C00018EF336	ginee	order	UPDATE	{"id": "GENIE68A2E4BAC9E77C00018EF336", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T08:30:47Z", "shopId": "SH625922A0E21B84000104B3E9", "channel": "TIKTOK_ID", "orderId": "SO68A2E4B252FAFF00019324B7", "createAt": "2025-08-18T08:30:41Z", "deleteAt": null, "orderStatus": "PAID", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:30:50.803998Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
30	GENIE68A2E4C04CEDFD0001A8491D	ginee	order	UPDATE	{"id": "GENIE68A2E4C04CEDFD0001A8491D", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T08:21:30Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2E28752FAFF000192D4B6", "createAt": "2025-08-18T08:21:24Z", "deleteAt": null, "orderStatus": "READY_TO_SHIP", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:30:56.856548Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
31	GENIE68A2E5A1C9E77C00018EFBC7	ginee	order	UPDATE	{"id": "GENIE68A2E5A1C9E77C00018EFBC7", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-15T09:03:47Z", "shopId": "SH625922A0E21B84000104B3E9", "channel": "TIKTOK_ID", "orderId": "SO689EF7B9E8288600010DA95E", "createAt": "2025-08-15T09:02:48Z", "deleteAt": null, "orderStatus": "DELIVERED", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:34:41.519441Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
32	GENIE68A2E5A7E21B8400018B848A	ginee	order	UPDATE	{"id": "GENIE68A2E5A7E21B8400018B848A", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T05:39:22Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2BC8146E0FB000139ACC4", "createAt": "2025-08-18T05:39:11Z", "deleteAt": null, "orderStatus": "SHIPPING", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:34:47.057494Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
33	GENIE68A2E6A34CEDFD00017B8AD8	ginee	order	UPDATE	{"id": "GENIE68A2E6A34CEDFD00017B8AD8", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T08:30:47Z", "shopId": "SH625922A0E21B84000104B3E9", "channel": "TIKTOK_ID", "orderId": "SO68A2E4B252FAFF00019324B7", "createAt": "2025-08-18T08:30:41Z", "deleteAt": null, "orderStatus": "READY_TO_SHIP", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:38:59.323010Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
34	GENIE68A2E6AD4CEDFD0001A85BF6	ginee	order	CREATE	{"id": "GENIE68A2E6AD4CEDFD0001A85BF6", "action": "CREATE", "entity": "order", "payload": {"payAt": null, "shopId": "SH659BCDE4590801000181E93B", "channel": "SHOPEE_ID", "orderId": "SO68A2E6AD52FAFF0001936977", "createAt": "2025-08-18T08:39:05Z", "deleteAt": null, "orderStatus": "PENDING_PAYMENT", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:39:09.188902Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
35	GENIE68A2E6B84CEDFD00017B8B8D	ginee	order	UPDATE	{"id": "GENIE68A2E6B84CEDFD00017B8B8D", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T08:39:17Z", "shopId": "SH659BCDE4590801000181E93B", "channel": "SHOPEE_ID", "orderId": "SO68A2E6AD52FAFF0001936977", "createAt": "2025-08-18T08:39:05Z", "deleteAt": null, "orderStatus": "PAID", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:39:20.911108Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
36	GENIE68A2E881CFF47E0001660565	ginee	order	UPDATE	{"id": "GENIE68A2E881CFF47E0001660565", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T07:19:22Z", "shopId": "SH625922A0E21B84000104B3E9", "channel": "TIKTOK_ID", "orderId": "SO68A2D39446E0FB00019B05BB", "createAt": "2025-08-18T07:17:38Z", "deleteAt": null, "orderStatus": "READY_TO_SHIP", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:46:57.392494Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
37	GENIE68A2E8844CEDFD0001A86BC0	ginee	order	UPDATE	{"id": "GENIE68A2E8844CEDFD0001A86BC0", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T03:59:18Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2A4F746E0FB0001361750", "createAt": "2025-08-18T03:58:46Z", "deleteAt": null, "orderStatus": "READY_TO_SHIP", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:47:00.068955Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
38	GENIE68A2E8E04CEDFD00017B9DD6	ginee	order	UPDATE	{"id": "GENIE68A2E8E04CEDFD00017B9DD6", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T03:54:14Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2A2BD4CEDFD0001730943", "createAt": "2025-08-18T03:49:16Z", "deleteAt": null, "orderStatus": "READY_TO_SHIP", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:48:32.508689Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
39	GENIE68A2E9004CEDFD0001A87070	ginee	order	UPDATE	{"id": "GENIE68A2E9004CEDFD0001A87070", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-15T10:14:38Z", "shopId": "SH659BCDE4590801000181E93B", "channel": "SHOPEE_ID", "orderId": "SO689F088CC9E77C0001A4231E", "createAt": "2025-08-15T10:14:34Z", "deleteAt": null, "orderStatus": "SHIPPING", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:49:04.690218Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
40	GENIE68A2E95D4CEDFD00017BA19C	ginee	order	UPDATE	{"id": "GENIE68A2E95D4CEDFD00017BA19C", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-15T13:12:37Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO689F321FC9E77C0001AA0553", "createAt": "2025-08-15T13:11:57Z", "deleteAt": null, "orderStatus": "SHIPPING", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:50:37.536031Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
41	GENIE68A2E968C9E77C00018F1DAC	ginee	order	UPDATE	{"id": "GENIE68A2E968C9E77C00018F1DAC", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T03:54:14Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2A2BD4CEDFD0001730943", "createAt": "2025-08-18T03:49:16Z", "deleteAt": null, "orderStatus": "CANCELLED", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T08:50:48.467480Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
42	GENIE68A2EC294CEDFD00017BBCA9	ginee	order	UPDATE	{"id": "GENIE68A2EC294CEDFD00017BBCA9", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T08:39:17Z", "shopId": "SH659BCDE4590801000181E93B", "channel": "SHOPEE_ID", "orderId": "SO68A2E6AD52FAFF0001936977", "createAt": "2025-08-18T08:39:05Z", "deleteAt": null, "orderStatus": "READY_TO_SHIP", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T09:02:33.729339Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
43	GENIE68A2EC82E21B8400018BC595	ginee	order	UPDATE	{"id": "GENIE68A2EC82E21B8400018BC595", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-18T08:21:30Z", "shopId": "SH5FE9552A5908010001036FD6", "channel": "SHOPEE_ID", "orderId": "SO68A2E28752FAFF000192D4B6", "createAt": "2025-08-18T08:21:24Z", "deleteAt": null, "orderStatus": "SHIPPING", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T09:04:02.031316Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
44	GENIE68A2EDD4E21B8400018BD4C2	ginee	order	UPDATE	{"id": "GENIE68A2EDD4E21B8400018BD4C2", "action": "UPDATE", "entity": "order", "payload": {"payAt": "2025-08-16T13:29:00Z", "shopId": "SH625922A0E21B84000104B3E9", "channel": "TIKTOK_ID", "orderId": "SO68A0877CC9E77C00011F0079", "createAt": "2025-08-16T13:28:27Z", "deleteAt": null, "orderStatus": "DELIVERED", "lastUpdateAt": null, "externalShopId": null}, "createAt": "2025-08-18T09:09:40.093619Z"}	\N	\N	\N	\N	\N	\N	\N	f	\N	0
\.


--
-- Data for Name: wishlists; Type: TABLE DATA; Schema: public; Owner: sneaker_user
--

COPY public.wishlists (id, user_id, product_id, created_at, updated_at) FROM stdin;
\.


--
-- Name: categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.categories_id_seq', 17, true);


--
-- Name: coupon_usages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.coupon_usages_id_seq', 1, false);


--
-- Name: coupons_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.coupons_id_seq', 10, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: ginee_product_mappings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.ginee_product_mappings_id_seq', 1, false);


--
-- Name: ginee_sync_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.ginee_sync_logs_id_seq', 74, true);


--
-- Name: google_sheets_sync_logs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.google_sheets_sync_logs_id_seq', 22, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: menu_navigation_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.menu_navigation_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.migrations_id_seq', 45, true);


--
-- Name: order_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.order_items_id_seq', 32, true);


--
-- Name: orders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.orders_id_seq', 32, true);


--
-- Name: points_transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.points_transactions_id_seq', 1, false);


--
-- Name: products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.products_id_seq', 83, true);


--
-- Name: shopping_cart_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.shopping_cart_id_seq', 1, false);


--
-- Name: user_addresses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.user_addresses_id_seq', 4, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.users_id_seq', 7, true);


--
-- Name: webhook_events_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.webhook_events_id_seq', 44, true);


--
-- Name: wishlists_id_seq; Type: SEQUENCE SET; Schema: public; Owner: sneaker_user
--

SELECT pg_catalog.setval('public.wishlists_id_seq', 9, true);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: categories categories_slug_unique; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_slug_unique UNIQUE (slug);


--
-- Name: coupon_usages coupon_usages_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.coupon_usages
    ADD CONSTRAINT coupon_usages_pkey PRIMARY KEY (id);


--
-- Name: coupons coupons_code_unique; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.coupons
    ADD CONSTRAINT coupons_code_unique UNIQUE (code);


--
-- Name: coupons coupons_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.coupons
    ADD CONSTRAINT coupons_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: ginee_product_mappings ginee_product_mappings_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.ginee_product_mappings
    ADD CONSTRAINT ginee_product_mappings_pkey PRIMARY KEY (id);


--
-- Name: ginee_product_mappings ginee_product_mappings_unique; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.ginee_product_mappings
    ADD CONSTRAINT ginee_product_mappings_unique UNIQUE (product_id, ginee_master_sku);


--
-- Name: ginee_sync_logs ginee_sync_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.ginee_sync_logs
    ADD CONSTRAINT ginee_sync_logs_pkey PRIMARY KEY (id);


--
-- Name: google_sheets_sync_logs google_sheets_sync_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.google_sheets_sync_logs
    ADD CONSTRAINT google_sheets_sync_logs_pkey PRIMARY KEY (id);


--
-- Name: google_sheets_sync_logs google_sheets_sync_logs_sync_id_unique; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.google_sheets_sync_logs
    ADD CONSTRAINT google_sheets_sync_logs_sync_id_unique UNIQUE (sync_id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: menu_navigation menu_navigation_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.menu_navigation
    ADD CONSTRAINT menu_navigation_pkey PRIMARY KEY (id);


--
-- Name: menu_navigation menu_navigation_slug_unique; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.menu_navigation
    ADD CONSTRAINT menu_navigation_slug_unique UNIQUE (slug);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: order_items order_items_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_pkey PRIMARY KEY (id);


--
-- Name: orders orders_order_number_unique; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_order_number_unique UNIQUE (order_number);


--
-- Name: orders orders_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: points_transactions points_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.points_transactions
    ADD CONSTRAINT points_transactions_pkey PRIMARY KEY (id);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- Name: products products_sku_unique; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_sku_unique UNIQUE (sku);


--
-- Name: products products_slug_unique; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_slug_unique UNIQUE (slug);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: shopping_cart shopping_cart_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.shopping_cart
    ADD CONSTRAINT shopping_cart_pkey PRIMARY KEY (id);


--
-- Name: shopping_cart unique_session_product_cart; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.shopping_cart
    ADD CONSTRAINT unique_session_product_cart UNIQUE (session_id, product_id);


--
-- Name: shopping_cart unique_user_product_cart; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.shopping_cart
    ADD CONSTRAINT unique_user_product_cart UNIQUE (user_id, product_id);


--
-- Name: wishlists unique_user_product_wishlist; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.wishlists
    ADD CONSTRAINT unique_user_product_wishlist UNIQUE (user_id, product_id);


--
-- Name: user_addresses user_addresses_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.user_addresses
    ADD CONSTRAINT user_addresses_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: voucher_sync_log voucher_sync_log_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.voucher_sync_log
    ADD CONSTRAINT voucher_sync_log_pkey PRIMARY KEY (id);


--
-- Name: voucher_usage voucher_usage_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.voucher_usage
    ADD CONSTRAINT voucher_usage_pkey PRIMARY KEY (id);


--
-- Name: voucher_usage voucher_usage_voucher_id_customer_id_unique; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.voucher_usage
    ADD CONSTRAINT voucher_usage_voucher_id_customer_id_unique UNIQUE (voucher_id, customer_id);


--
-- Name: vouchers vouchers_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.vouchers
    ADD CONSTRAINT vouchers_pkey PRIMARY KEY (id);


--
-- Name: vouchers vouchers_voucher_code_unique; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.vouchers
    ADD CONSTRAINT vouchers_voucher_code_unique UNIQUE (voucher_code);


--
-- Name: webhook_events webhook_events_event_id_unique; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.webhook_events
    ADD CONSTRAINT webhook_events_event_id_unique UNIQUE (event_id);


--
-- Name: webhook_events webhook_events_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.webhook_events
    ADD CONSTRAINT webhook_events_pkey PRIMARY KEY (id);


--
-- Name: wishlists wishlists_pkey; Type: CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.wishlists
    ADD CONSTRAINT wishlists_pkey PRIMARY KEY (id);


--
-- Name: categories_is_active_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX categories_is_active_index ON public.categories USING btree (is_active);


--
-- Name: categories_menu_placement_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX categories_menu_placement_index ON public.categories USING btree (menu_placement);


--
-- Name: categories_show_in_menu_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX categories_show_in_menu_index ON public.categories USING btree (show_in_menu);


--
-- Name: categories_slug_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX categories_slug_index ON public.categories USING btree (slug);


--
-- Name: categories_sort_order_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX categories_sort_order_index ON public.categories USING btree (sort_order);


--
-- Name: coupon_usages_coupon_id_used_at_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX coupon_usages_coupon_id_used_at_index ON public.coupon_usages USING btree (coupon_id, used_at);


--
-- Name: coupon_usages_order_id_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX coupon_usages_order_id_index ON public.coupon_usages USING btree (order_id);


--
-- Name: coupon_usages_user_id_coupon_id_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX coupon_usages_user_id_coupon_id_index ON public.coupon_usages USING btree (user_id, coupon_id);


--
-- Name: coupons_code_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX coupons_code_index ON public.coupons USING btree (code);


--
-- Name: coupons_is_active_starts_at_expires_at_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX coupons_is_active_starts_at_expires_at_index ON public.coupons USING btree (is_active, starts_at, expires_at);


--
-- Name: ginee_product_mappings_ginee_master_sku_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX ginee_product_mappings_ginee_master_sku_index ON public.ginee_product_mappings USING btree (ginee_master_sku);


--
-- Name: ginee_product_mappings_ginee_product_id_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX ginee_product_mappings_ginee_product_id_index ON public.ginee_product_mappings USING btree (ginee_product_id);


--
-- Name: ginee_product_mappings_master_sku_idx; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX ginee_product_mappings_master_sku_idx ON public.ginee_product_mappings USING btree (ginee_master_sku);


--
-- Name: ginee_product_mappings_sync_composite_idx; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX ginee_product_mappings_sync_composite_idx ON public.ginee_product_mappings USING btree (sync_enabled, stock_sync_enabled);


--
-- Name: ginee_product_mappings_sync_enabled_idx; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX ginee_product_mappings_sync_enabled_idx ON public.ginee_product_mappings USING btree (sync_enabled);


--
-- Name: ginee_sync_logs_created_at_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX ginee_sync_logs_created_at_index ON public.ginee_sync_logs USING btree (created_at);


--
-- Name: ginee_sync_logs_op_status_idx; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX ginee_sync_logs_op_status_idx ON public.ginee_sync_logs USING btree (operation_type, status);


--
-- Name: ginee_sync_logs_operation_type_idx; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX ginee_sync_logs_operation_type_idx ON public.ginee_sync_logs USING btree (operation_type);


--
-- Name: ginee_sync_logs_session_id_idx; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX ginee_sync_logs_session_id_idx ON public.ginee_sync_logs USING btree (session_id);


--
-- Name: ginee_sync_logs_sku_idx; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX ginee_sync_logs_sku_idx ON public.ginee_sync_logs USING btree (sku);


--
-- Name: ginee_sync_logs_status_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX ginee_sync_logs_status_index ON public.ginee_sync_logs USING btree (status);


--
-- Name: ginee_sync_logs_type_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX ginee_sync_logs_type_index ON public.ginee_sync_logs USING btree (type);


--
-- Name: ginee_sync_logs_type_status_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX ginee_sync_logs_type_status_index ON public.ginee_sync_logs USING btree (type, status);


--
-- Name: google_sheets_sync_logs_initiated_by_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX google_sheets_sync_logs_initiated_by_index ON public.google_sheets_sync_logs USING btree (initiated_by);


--
-- Name: google_sheets_sync_logs_spreadsheet_id_started_at_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX google_sheets_sync_logs_spreadsheet_id_started_at_index ON public.google_sheets_sync_logs USING btree (spreadsheet_id, started_at);


--
-- Name: google_sheets_sync_logs_status_started_at_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX google_sheets_sync_logs_status_started_at_index ON public.google_sheets_sync_logs USING btree (status, started_at);


--
-- Name: google_sheets_sync_logs_sync_strategy_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX google_sheets_sync_logs_sync_strategy_index ON public.google_sheets_sync_logs USING btree (sync_strategy);


--
-- Name: idx_ginee_sync_logs_created_at; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX idx_ginee_sync_logs_created_at ON public.ginee_sync_logs USING btree (created_at DESC);


--
-- Name: idx_ginee_sync_logs_operation_type; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX idx_ginee_sync_logs_operation_type ON public.ginee_sync_logs USING btree (operation_type);


--
-- Name: idx_ginee_sync_logs_sku; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX idx_ginee_sync_logs_sku ON public.ginee_sync_logs USING btree (sku);


--
-- Name: idx_ginee_sync_logs_status; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX idx_ginee_sync_logs_status ON public.ginee_sync_logs USING btree (status);


--
-- Name: idx_products_ginee_push; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX idx_products_ginee_push ON public.products USING btree (ginee_last_stock_push);


--
-- Name: idx_products_ginee_sync; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX idx_products_ginee_sync ON public.products USING btree (ginee_last_stock_sync);


--
-- Name: idx_products_sku; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX idx_products_sku ON public.products USING btree (sku);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: menu_navigation_is_active_sort_order_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX menu_navigation_is_active_sort_order_index ON public.menu_navigation USING btree (is_active, sort_order);


--
-- Name: menu_navigation_parent_id_sort_order_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX menu_navigation_parent_id_sort_order_index ON public.menu_navigation USING btree (parent_id, sort_order);


--
-- Name: menu_navigation_slug_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX menu_navigation_slug_index ON public.menu_navigation USING btree (slug);


--
-- Name: order_items_order_id_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX order_items_order_id_index ON public.order_items USING btree (order_id);


--
-- Name: order_items_product_id_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX order_items_product_id_index ON public.order_items USING btree (product_id);


--
-- Name: orders_created_at_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX orders_created_at_index ON public.orders USING btree (created_at);


--
-- Name: orders_customer_email_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX orders_customer_email_index ON public.orders USING btree (customer_email);


--
-- Name: orders_order_number_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX orders_order_number_index ON public.orders USING btree (order_number);


--
-- Name: orders_payment_status_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX orders_payment_status_index ON public.orders USING btree (payment_status);


--
-- Name: orders_status_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX orders_status_index ON public.orders USING btree (status);


--
-- Name: orders_user_id_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX orders_user_id_index ON public.orders USING btree (user_id);


--
-- Name: points_transactions_reference_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX points_transactions_reference_index ON public.points_transactions USING btree (reference);


--
-- Name: points_transactions_type_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX points_transactions_type_index ON public.points_transactions USING btree (type);


--
-- Name: points_transactions_user_id_created_at_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX points_transactions_user_id_created_at_index ON public.points_transactions USING btree (user_id, created_at);


--
-- Name: points_transactions_user_id_type_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX points_transactions_user_id_type_index ON public.points_transactions USING btree (user_id, type);


--
-- Name: products_brand_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_brand_index ON public.products USING btree (brand);


--
-- Name: products_brand_is_active_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_brand_is_active_index ON public.products USING btree (brand, is_active);


--
-- Name: products_category_id_is_active_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_category_id_is_active_index ON public.products USING btree (category_id, is_active);


--
-- Name: products_gender_target_gin_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_gender_target_gin_index ON public.products USING gin (gender_target) WHERE (gender_target IS NOT NULL);


--
-- Name: products_ginee_id_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_ginee_id_index ON public.products USING btree (ginee_id);


--
-- Name: products_ginee_last_sync_idx; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_ginee_last_sync_idx ON public.products USING btree (ginee_last_sync);


--
-- Name: products_ginee_last_sync_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_ginee_last_sync_index ON public.products USING btree (ginee_last_sync);


--
-- Name: products_ginee_product_id_idx; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_ginee_product_id_idx ON public.products USING btree (ginee_product_id);


--
-- Name: products_ginee_sync_composite_idx; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_ginee_sync_composite_idx ON public.products USING btree (ginee_sync_status, ginee_last_sync);


--
-- Name: products_ginee_sync_status_idx; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_ginee_sync_status_idx ON public.products USING btree (ginee_sync_status);


--
-- Name: products_ginee_sync_status_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_ginee_sync_status_index ON public.products USING btree (ginee_sync_status);


--
-- Name: products_is_active_is_featured_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_is_active_is_featured_index ON public.products USING btree (is_active, is_featured);


--
-- Name: products_is_active_published_at_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_is_active_published_at_index ON public.products USING btree (is_active, published_at);


--
-- Name: products_is_active_stock_quantity_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_is_active_stock_quantity_index ON public.products USING btree (is_active, stock_quantity);


--
-- Name: products_product_type_active_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_product_type_active_index ON public.products USING btree (product_type, is_active) WHERE (product_type IS NOT NULL);


--
-- Name: products_product_type_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_product_type_index ON public.products USING btree (product_type);


--
-- Name: products_sale_price_is_featured_sale_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_sale_price_is_featured_sale_index ON public.products USING btree (sale_price, is_featured_sale);


--
-- Name: products_sku_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_sku_index ON public.products USING btree (sku);


--
-- Name: products_sku_parent_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_sku_parent_index ON public.products USING btree (sku_parent);


--
-- Name: products_slug_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX products_slug_index ON public.products USING btree (slug);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: shopping_cart_product_id_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX shopping_cart_product_id_index ON public.shopping_cart USING btree (product_id);


--
-- Name: shopping_cart_session_id_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX shopping_cart_session_id_index ON public.shopping_cart USING btree (session_id);


--
-- Name: shopping_cart_user_id_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX shopping_cart_user_id_index ON public.shopping_cart USING btree (user_id);


--
-- Name: user_addresses_one_primary_per_user; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE UNIQUE INDEX user_addresses_one_primary_per_user ON public.user_addresses USING btree (user_id) WHERE ((is_primary = true) AND (is_active = true));


--
-- Name: user_addresses_user_id_is_active_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX user_addresses_user_id_is_active_index ON public.user_addresses USING btree (user_id, is_active);


--
-- Name: user_addresses_user_id_is_active_is_primary_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX user_addresses_user_id_is_active_is_primary_index ON public.user_addresses USING btree (user_id, is_active, is_primary);


--
-- Name: user_addresses_user_id_is_primary_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX user_addresses_user_id_is_primary_index ON public.user_addresses USING btree (user_id, is_primary);


--
-- Name: users_customer_tier_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX users_customer_tier_index ON public.users USING btree (customer_tier);


--
-- Name: users_google_id_unique; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE UNIQUE INDEX users_google_id_unique ON public.users USING btree (google_id) WHERE (google_id IS NOT NULL);


--
-- Name: users_points_balance_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX users_points_balance_index ON public.users USING btree (points_balance);


--
-- Name: users_spending_6_months_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX users_spending_6_months_index ON public.users USING btree (spending_6_months);


--
-- Name: users_tier_period_start_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX users_tier_period_start_index ON public.users USING btree (tier_period_start);


--
-- Name: users_total_orders_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX users_total_orders_index ON public.users USING btree (total_orders);


--
-- Name: users_total_spent_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX users_total_spent_index ON public.users USING btree (total_spent);


--
-- Name: users_total_spent_total_orders_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX users_total_spent_total_orders_index ON public.users USING btree (total_spent, total_orders);


--
-- Name: users_zodiac_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX users_zodiac_index ON public.users USING btree (zodiac);


--
-- Name: voucher_usage_customer_id_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX voucher_usage_customer_id_index ON public.voucher_usage USING btree (customer_id);


--
-- Name: voucher_usage_used_at_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX voucher_usage_used_at_index ON public.voucher_usage USING btree (used_at);


--
-- Name: voucher_usage_voucher_id_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX voucher_usage_voucher_id_index ON public.voucher_usage USING btree (voucher_id);


--
-- Name: vouchers_category_customer_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX vouchers_category_customer_index ON public.vouchers USING btree (category_customer);


--
-- Name: vouchers_is_active_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX vouchers_is_active_index ON public.vouchers USING btree (is_active);


--
-- Name: vouchers_start_date_end_date_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX vouchers_start_date_end_date_index ON public.vouchers USING btree (start_date, end_date);


--
-- Name: vouchers_sync_status_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX vouchers_sync_status_index ON public.vouchers USING btree (sync_status);


--
-- Name: vouchers_voucher_code_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX vouchers_voucher_code_index ON public.vouchers USING btree (voucher_code);


--
-- Name: webhook_events_created_at_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX webhook_events_created_at_index ON public.webhook_events USING btree (created_at);


--
-- Name: webhook_events_entity_action_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX webhook_events_entity_action_index ON public.webhook_events USING btree (entity, action);


--
-- Name: webhook_events_event_type_idx; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX webhook_events_event_type_idx ON public.webhook_events USING btree (event_type);


--
-- Name: webhook_events_processed_idx; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX webhook_events_processed_idx ON public.webhook_events USING btree (processed);


--
-- Name: webhook_events_retry_count_idx; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX webhook_events_retry_count_idx ON public.webhook_events USING btree (retry_count);


--
-- Name: webhook_events_source_entity_idx; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX webhook_events_source_entity_idx ON public.webhook_events USING btree (source, entity);


--
-- Name: wishlists_product_id_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX wishlists_product_id_index ON public.wishlists USING btree (product_id);


--
-- Name: wishlists_user_id_index; Type: INDEX; Schema: public; Owner: sneaker_user
--

CREATE INDEX wishlists_user_id_index ON public.wishlists USING btree (user_id);


--
-- Name: users trigger_update_zodiac; Type: TRIGGER; Schema: public; Owner: sneaker_user
--

CREATE TRIGGER trigger_update_zodiac BEFORE INSERT OR UPDATE OF birthdate ON public.users FOR EACH ROW EXECUTE FUNCTION public.update_zodiac_trigger();


--
-- Name: coupon_usages coupon_usages_coupon_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.coupon_usages
    ADD CONSTRAINT coupon_usages_coupon_id_foreign FOREIGN KEY (coupon_id) REFERENCES public.coupons(id) ON DELETE CASCADE;


--
-- Name: coupon_usages coupon_usages_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.coupon_usages
    ADD CONSTRAINT coupon_usages_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: coupon_usages coupon_usages_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.coupon_usages
    ADD CONSTRAINT coupon_usages_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ginee_product_mappings ginee_product_mappings_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.ginee_product_mappings
    ADD CONSTRAINT ginee_product_mappings_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: menu_navigation menu_navigation_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.menu_navigation
    ADD CONSTRAINT menu_navigation_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.menu_navigation(id) ON DELETE CASCADE;


--
-- Name: order_items order_items_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.order_items
    ADD CONSTRAINT order_items_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE CASCADE;


--
-- Name: orders orders_coupon_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_coupon_id_foreign FOREIGN KEY (coupon_id) REFERENCES public.coupons(id) ON DELETE SET NULL;


--
-- Name: points_transactions points_transactions_order_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.points_transactions
    ADD CONSTRAINT points_transactions_order_id_foreign FOREIGN KEY (order_id) REFERENCES public.orders(id) ON DELETE SET NULL;


--
-- Name: points_transactions points_transactions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.points_transactions
    ADD CONSTRAINT points_transactions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: products products_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.categories(id) ON DELETE CASCADE;


--
-- Name: shopping_cart shopping_cart_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.shopping_cart
    ADD CONSTRAINT shopping_cart_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: user_addresses user_addresses_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.user_addresses
    ADD CONSTRAINT user_addresses_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: voucher_usage voucher_usage_voucher_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.voucher_usage
    ADD CONSTRAINT voucher_usage_voucher_id_foreign FOREIGN KEY (voucher_id) REFERENCES public.vouchers(id) ON DELETE CASCADE;


--
-- Name: wishlists wishlists_product_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: sneaker_user
--

ALTER TABLE ONLY public.wishlists
    ADD CONSTRAINT wishlists_product_id_foreign FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: pg_database_owner
--

GRANT ALL ON SCHEMA public TO sneaker_user;


--
-- Name: DEFAULT PRIVILEGES FOR SEQUENCES; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON SEQUENCES TO sneaker_user;


--
-- Name: DEFAULT PRIVILEGES FOR TABLES; Type: DEFAULT ACL; Schema: public; Owner: postgres
--

ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public GRANT ALL ON TABLES TO sneaker_user;


--
-- PostgreSQL database dump complete
--

