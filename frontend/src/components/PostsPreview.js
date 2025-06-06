import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { format } from 'date-fns';
import { Container, Row, Col, Card, Form, Button, Alert, ListGroup, Pagination } from 'react-bootstrap';

const PostsPreview = () => {
    const [entries, setEntries] = useState([]);
    const [feeds, setFeeds] = useState([]);
    const [selectedFeed, setSelectedFeed] = useState('');
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [searchTerm, setSearchTerm] = useState('');
    const [currentPage, setCurrentPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchFeeds = async () => {
            try {
                const response = await axios.get('http://localhost:3000/api/feeds');
                setFeeds(response.data.feeds || []);
            } catch (err) {
                setError('Failed to load feeds');
                console.error('Error fetching feeds:', err);
            }
        };
        fetchFeeds();
    }, []);

    useEffect(() => {
        const fetchPosts = async () => {
            try {
                setLoading(true);
                const params = new URLSearchParams({
                    page: currentPage,
                    perPage: 10
                });

                if (selectedFeed) {
                    params.append('feedId', selectedFeed);
                }
                if (startDate) {
                    params.append('startDate', startDate);
                }
                if (endDate) {
                    params.append('endDate', endDate);
                }
                if (searchTerm) {
                    params.append('search', searchTerm);
                }

                const response = await axios.get(`http://localhost:3000/api/posts?${params}`);
                setEntries(response.data.entries || []);
                setTotalPages(response.data.pagination?.lastPage || 1);
                setError(null);
            } catch (err) {
                setError('Failed to load posts');
                console.error('Error fetching posts:', err);
                setEntries([]);
            } finally {
                setLoading(false);
            }
        };

        fetchPosts();
    }, [selectedFeed, startDate, endDate, searchTerm, currentPage]);

    const handlePageChange = (page) => {
        setCurrentPage(page);
    };

    const renderPaginationItems = () => {
        const items = [];
        const maxVisiblePages = 5; // Number of page numbers to show
        const halfVisible = Math.floor(maxVisiblePages / 2);

        // Calculate start and end page numbers
        let startPage = Math.max(1, currentPage - halfVisible);
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

        // Adjust start page if we're near the end
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }

        // First page and ellipsis
        if (startPage > 1) {
            items.push(
                <Pagination.Item key={1} onClick={() => handlePageChange(1)}>
                    1
                </Pagination.Item>
            );
            if (startPage > 2) {
                items.push(<Pagination.Ellipsis key="ellipsis-start" disabled />);
            }
        }

        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            items.push(
                <Pagination.Item
                    key={i}
                    active={currentPage === i}
                    onClick={() => handlePageChange(i)}
                >
                    {i}
                </Pagination.Item>
            );
        }

        // Last page and ellipsis
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                items.push(<Pagination.Ellipsis key="ellipsis-end" disabled />);
            }
            items.push(
                <Pagination.Item key={totalPages} onClick={() => handlePageChange(totalPages)}>
                    {totalPages}
                </Pagination.Item>
            );
        }

        return items;
    };

    return (
        <Container className="mt-4">
            <h2>Posts Preview</h2>
            
            {/* Filters */}
            <Row className="mb-4">
                <Col md={3}>
                    <Form.Group>
                        <Form.Label>Feed</Form.Label>
                        <Form.Control
                            as="select"
                            value={selectedFeed}
                            onChange={(e) => setSelectedFeed(e.target.value)}
                        >
                            <option value="">All Feeds</option>
                            {feeds.map(feed => (
                                <option key={feed.id} value={feed.id}>
                                    {feed.title}
                                </option>
                            ))}
                        </Form.Control>
                    </Form.Group>
                </Col>
                <Col md={3}>
                    <Form.Group>
                        <Form.Label>Start Date</Form.Label>
                        <Form.Control
                            type="date"
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                        />
                    </Form.Group>
                </Col>
                <Col md={3}>
                    <Form.Group>
                        <Form.Label>End Date</Form.Label>
                        <Form.Control
                            type="date"
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                        />
                    </Form.Group>
                </Col>
                <Col md={3}>
                    <Form.Group>
                        <Form.Label>Search</Form.Label>
                        <Form.Control
                            type="text"
                            placeholder="Search titles..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                        />
                    </Form.Group>
                </Col>
            </Row>

            {/* Error Message */}
            {error && (
                <div className="alert alert-danger" role="alert">
                    {error}
                </div>
            )}

            {/* Loading State */}
            {loading && (
                <div className="text-center">
                    <div className="spinner-border" role="status">
                    </div>
                </div>
            )}

            {/* Posts List */}
            {!loading && !error && (
                <>
                    <ListGroup>
                        {entries.map(entry => (
                            <ListGroup.Item key={entry.id} className="d-flex justify-content-between align-items-center">
                                <div>
                                    <a 
                                        href={entry.url} 
                                        target="_blank" 
                                        rel="noopener noreferrer"
                                        title="Opens in new tab"
                                        className="text-decoration-none"
                                    >
                                        {entry.title}
                                        <i className="fas fa-external-link-alt ml-2" style={{ fontSize: '0.8rem' }}></i>
                                    </a>
                                    <small className="text-muted d-block">
                                        {new Date(entry.published_at).toLocaleDateString()}
                                    </small>
                                </div>
                            </ListGroup.Item>
                        ))}
                    </ListGroup>

                    {/* Pagination */}
                    {totalPages > 1 && (
                        <div className="d-flex justify-content-center mt-4">
                            <Pagination>
                                <Pagination.First 
                                    onClick={() => handlePageChange(1)} 
                                    disabled={currentPage === 1}
                                />
                                <Pagination.Prev 
                                    onClick={() => handlePageChange(currentPage - 1)} 
                                    disabled={currentPage === 1}
                                />
                                
                                {renderPaginationItems()}

                                <Pagination.Next 
                                    onClick={() => handlePageChange(currentPage + 1)} 
                                    disabled={currentPage === totalPages}
                                />
                                <Pagination.Last 
                                    onClick={() => handlePageChange(totalPages)} 
                                    disabled={currentPage === totalPages}
                                />
                            </Pagination>
                        </div>
                    )}
                </>
            )}
        </Container>
    );
};

export default PostsPreview; 