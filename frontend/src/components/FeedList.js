import React, { useState, useEffect } from 'react';
import { Container, Row, Col, Card, Button, Alert, Spinner } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import axios from 'axios';

const FeedList = () => {
    const [feeds, setFeeds] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [refreshingFeeds, setRefreshingFeeds] = useState(new Set());
    const [refreshMessage, setRefreshMessage] = useState(null);

    useEffect(() => {
        fetchFeeds();
    }, []);

    const fetchFeeds = async () => {
        try {
            const response = await axios.get('/api/feeds');
            if (response.data && Array.isArray(response.data.feeds)) {
                setFeeds(response.data.feeds);
                setError(null);
            } else {
                setError('Invalid response format');
                setFeeds([]);
            }
        } catch (err) {
            setError('Failed to load feeds');
            setFeeds([]);
        } finally {
            setLoading(false);
        }
    };

    const handleRefresh = async (feedId) => {
        if (!feedId) {
            setError('Invalid feed ID');
            return;
        }

        try {
            setRefreshingFeeds(prev => new Set([...prev, feedId]));
            setRefreshMessage('Refreshing feed...');

            const response = await axios.post(`/api/feeds/${feedId}/refresh`);
            
            if (response.data.status === 'success') {
                setRefreshMessage(response.data.message);
                // Update the feed in the list
                setFeeds(prevFeeds => 
                    prevFeeds.map(feed => 
                        feed.id === feedId ? response.data.feed : feed
                    )
                );
            } else if (response.data.status === 'error') {
                setError(response.data.error);
            }
        } catch (err) {
            setError(err.response?.data?.error || 'Failed to refresh feed');
        } finally {
            setRefreshingFeeds(prev => {
                const newSet = new Set(prev);
                newSet.delete(feedId);
                return newSet;
            });
            // Clear the refresh message after 3 seconds
            setTimeout(() => setRefreshMessage(null), 3000);
        }
    };

    const handleDelete = async (feedId) => {
        if (!feedId) {
            setError('Invalid feed ID');
            return;
        }

        if (window.confirm('Are you sure you want to delete this feed?')) {
            try {
                await axios.delete(`/api/feeds/${feedId}`);
                setFeeds(feeds.filter(feed => feed.id !== feedId));
            } catch (err) {
                setError('Failed to delete feed');
            }
        }
    };

    if (loading) {
        return (
            <Container>
                <Alert variant="info">Loading feeds...</Alert>
            </Container>
        );
    }

    return (
        <Container>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h2>Feeds</h2>
                <Button as={Link} to="/feeds/add" variant="primary">
                    Add Feed
                </Button>
            </div>

            {error && (
                <Alert variant="danger" className="mb-4" onClose={() => setError(null)} dismissible>
                    {error}
                </Alert>
            )}

            {refreshMessage && (
                <Alert variant="info" className="mb-4" onClose={() => setRefreshMessage(null)} dismissible>
                    {refreshMessage}
                </Alert>
            )}

            {feeds.length === 0 ? (
                <Alert variant="info">
                    No feeds found. <Link to="/feeds/add">Add your first feed</Link>
                </Alert>
            ) : (
                <Row>
                    {feeds.map(feed => (
                        <Col key={feed.id} md={6} lg={4} className="mb-4">
                            <Card>
                                <Card.Body>
                                    <Card.Title>{feed.title}</Card.Title>
                                    <Card.Text>
                                        <small className="text-muted">
                                            Last updated: {new Date(feed.last_updated).toLocaleString()}
                                        </small>
                                    </Card.Text>
                                    <div className="d-flex justify-content-between">
                                        <div>
                                            <Button
                                                variant="outline-primary"
                                                size="sm"
                                                className="me-2"
                                                onClick={() => handleRefresh(feed.id)}
                                                disabled={refreshingFeeds.has(feed.id)}
                                            >
                                                {refreshingFeeds.has(feed.id) ? (
                                                    <>
                                                        <Spinner
                                                            as="span"
                                                            animation="border"
                                                            size="sm"
                                                            role="status"
                                                            aria-hidden="true"
                                                            className="me-2"
                                                        />
                                                        Refreshing...
                                                    </>
                                                ) : (
                                                    'Refresh'
                                                )}
                                            </Button>
                                            <Button
                                                as={Link}
                                                to={`/feeds/${feed.id}`}
                                                variant="outline-secondary"
                                                size="sm"
                                            >
                                                View Posts
                                            </Button>
                                        </div>
                                        <Button
                                            variant="outline-danger"
                                            size="sm"
                                            onClick={() => handleDelete(feed.id)}
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </Card.Body>
                            </Card>
                        </Col>
                    ))}
                </Row>
            )}
        </Container>
    );
};

export default FeedList; 